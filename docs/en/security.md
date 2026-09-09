# 🔐 Security & Compliance

* **Capability-based access control:** Only users with `report/unlocker:view` can access the
  report.
* **Session key verification:** All modifications (edit, delete) require a valid session key.
* **Server-side validation:** Restriction changes are validated against the course context.
* **Audit-friendly:** No bulk deletions without explicit user confirmation.
* **Privacy compliant:** This plugin stores no personal user data. It implements Moodle's
  Privacy API (`null_provider`) and is fully GDPR/LGPD-compliant.

### 🤖 AI Assistant

The optional AI assistant only activates once either Moodle's own `core_ai` (4.5+) or the
[AI Hub](https://moodle.org/plugins/local_aihub) plugin is configured with a provider key. It
sends only the teacher's plain-language edit request — never any student data — generates a
preview, and applies nothing without explicit confirmation.
