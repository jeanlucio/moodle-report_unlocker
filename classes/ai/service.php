<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * AI provider bridge for report_unlocker.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_unlocker\ai;

/**
 * Detects and delegates AI calls following the shared ecosystem ladder:
 *   1. local_aihub (if installed) — resolves personal → site BYOK keys
 *   2. Moodle core_ai subsystem (direct, when the hub has no key or is not installed)
 *
 * The hub owns the BYOK key precedence; core_ai is this plugin's own institutional
 * fallback, so a site with core_ai configured works without the hub installed. The
 * AI button in the UI is hidden when has_ai() returns false.
 *
 * @package    report_unlocker
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service {
    /**
     * Returns true when at least one AI provider is available.
     *
     * @return bool
     */
    public static function has_ai(): bool {
        if (class_exists(\local_aihub\ai::class) && \local_aihub\ai::is_available()) {
            return true;
        }
        return self::has_core_ai();
    }

    /**
     * Sends a raw prompt through the available provider chain and returns the result.
     *
     * Routes to the AI Hub (local_aihub) first, which resolves personal then site
     * BYOK keys. Falls back to the Moodle core_ai subsystem when the hub returns no
     * result or is not installed.
     *
     * @param string $prompt The full prompt text.
     * @param string $description Short label of what is being generated, for the hub usage log.
     * @return array Result with keys: success (bool), data (string), message (string), provider (string).
     */
    public static function send(string $prompt, string $description = ''): array {
        $lasterror = ['success' => false, 'message' => '', 'data' => '', 'provider' => ''];

        if (class_exists(\local_aihub\ai::class)) {
            $result = \local_aihub\ai::generate_text('', $prompt, true, 'report_unlocker', $description);
            if (!empty($result['success'])) {
                return $result;
            }
            // Preserve a real failure (e.g. an invalid key) so it is not masked as "no source".
            if (!empty($result['message'])) {
                $lasterror = $result;
            }
        }

        if (self::has_core_ai()) {
            $result = self::call_core_ai($prompt);
            if ($result['success'] || !empty($result['message'])) {
                return $result;
            }
        }

        return $lasterror;
    }

    /**
     * Returns true when the Moodle core_ai subsystem has at least one provider
     * configured and enabled for text generation.
     *
     * Compatible with Moodle 4.5+ — the manager is retrieved via the dependency
     * container, which injects the dependencies for the running version.
     *
     * @return bool
     */
    private static function has_core_ai(): bool {
        if (
            !class_exists(\core_ai\manager::class)
            || !class_exists(\core_ai\aiactions\generate_text::class)
        ) {
            return false;
        }

        try {
            $actionclass = \core_ai\aiactions\generate_text::class;
            $manager = \core\di::get(\core_ai\manager::class);
            $providers = $manager->get_providers_for_actions([$actionclass], true);
            return !empty($providers[$actionclass]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Calls the Moodle core_ai subsystem with the given prompt.
     *
     * @param string $prompt The prompt text.
     * @return array Result with keys: success, data, message, provider.
     */
    private static function call_core_ai(string $prompt): array {
        global $USER;

        try {
            $manager = \core\di::get(\core_ai\manager::class);

            $action = new \core_ai\aiactions\generate_text(
                contextid: \context_system::instance()->id,
                userid: (int) $USER->id,
                prompttext: $prompt,
            );

            $response = $manager->process_action($action);

            if (!$response->get_success()) {
                return ['success' => false, 'message' => 'core_ai: provider returned failure', 'data' => '', 'provider' => ''];
            }

            $data = $response->get_response_data();
            $content = (string) ($data['generatedcontent'] ?? '');

            if ($content === '') {
                return ['success' => false, 'message' => 'core_ai: empty response', 'data' => '', 'provider' => ''];
            }

            return ['success' => true, 'data' => $content, 'message' => '', 'provider' => 'Moodle AI'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'core_ai: ' . $e->getMessage(), 'data' => '', 'provider' => ''];
        }
    }
}
