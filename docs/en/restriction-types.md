# 🌱 Restriction Types Reference

| Type | Description | Example |
|------|-------------|---------|
| **Date** | Unlocks/locks at a specific date and time. | "Available after 2026-03-20 14:00" |
| **Group** | Restricts to members of a specific Moodle group. | "Visible to: Group A" |
| **Grouping** | Restricts to groups within a grouping. | "Visible to: Grouping 'Team Project'" |
| **Grade** | Restricts based on a grade item's score (min/max/range). | "Requires minimum 70% in Quiz 1" |
| **Completion** | Restricts based on activity completion status. | "Requires: Quiz 2 completed with pass" |
| **Profile** | Restricts based on user profile fields (standard or custom). | "Visible to: Department = Engineering" |
| **PlayerHUD** | (Requires `availability_playerhud` + `block_playerhud`) Restricts by player progression. | "Requires: Level ≥ 5", "Must own: Dragon Egg" |

Any restriction type not listed above — from a third-party availability plugin Unlocker does
not know how to edit — is still shown as read-only and can be removed like any other condition.
