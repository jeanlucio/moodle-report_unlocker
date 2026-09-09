# ✨ Features

* 📋 **Unified Dashboard:** View all activity restrictions in one place.
* 🔍 **Advanced Filtering:**
  * Filter by activity section
  * Filter by restriction type (date, group, grade, completion, profile, PlayerHUD, etc.)
  * Search by activity name
  * Combine multiple filters at once
* 🎯 **Supported Restriction Types (inline editing):**
  * 📅 **Date-based** — Activities unlock/lock on specific dates and times
  * 👥 **Group-based** — Restrict by Moodle group membership
  * 👤 **Grouping-based** — Restrict by grouping
  * 📊 **Grade-based** — Restrict by activity grades (minimum, maximum, range)
  * ✅ **Completion-based** — Restrict by activity completion status
  * 🆔 **Profile-based** — Restrict by user profile fields (standard and custom fields)
  * 🎮 **PlayerHUD-based** — Restrict by player level, items, or character class (requires
    `availability_playerhud` and `block_playerhud`)
  * 🔗 **Nested restriction groups** — Groups of AND/OR conditions displayed as **"Nested
    restriction group (N)"** (where `N` is the number of child conditions). You can hide them
    from students or open the native activity settings to edit the group directly.
* ✏️ **Bulk Management:**
  * Edit restrictions directly from the report
  * Delete individual restrictions
  * Remove all restrictions matching the current filter in one action
  * **Operator selector** per activity/section: choose whether the student must match *all*,
    *any*, *not all*, or *not any* of the listed conditions
  * **Visibility toggle per condition** (operator *all* / *not any*): control whether a hidden
    condition is shown greyed-out or fully invisible to students
  * **Global visibility toggle** (operator *any* / *not all*): single flag applied to all
    conditions in the group
* 🎨 **Readable Descriptions:** Each restriction displays a human-friendly summary (e.g.,
  "Created after 2026-03-15 14:30")
* 🤖 **AI Assistant:** Describe the desired changes in natural language; the assistant shows a
  preview and only applies them after confirmation. Requires `core_ai` configured in Moodle
  (4.5+) or the `local_aihub` plugin.
* 💾 **Safe Modifications:** Session key verification protects against accidental bulk changes
* 📱 **Responsive Design:** Works on desktop and tablet views
