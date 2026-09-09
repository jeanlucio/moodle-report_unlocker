# 📖 Usage

#### Accessing the Report

1. Navigate to a course (as a teacher or administrator).
2. In the left-side navigation menu, under **Course administration**, click **Unlocker**.
   (The link appears only for users with the `report/unlocker:view` capability.)

#### Understanding the Dashboard

* **Activity List:** All activities and resources in the course are listed with their current
  restrictions.
* **Restriction Display:** Each activity shows all applied conditions in a human-readable
  format.
* **Filter Panel:** At the top:
  * **Search box:** Type to filter by activity name (real-time)
  * **Section dropdown:** Show restrictions only in a specific section
  * **Restriction Type dropdown:** Show only a specific type of condition
* **Inline Editing:** Each restriction is editable directly in the report — change dates, select
  groups, adjust grade thresholds, etc. without navigating away.
* **Remove checkbox:** Mark individual restrictions for removal; click **Save all changes** once
  to persist every edit and deletion together.
* **Remove all visible:** Marks all restrictions currently shown (respecting active filters) for
  removal and saves automatically after a confirmation dialog.

#### Common Workflows

**Scenario 1: Find all date-based restrictions expiring soon**

1. Set **Restriction Type** filter to `Date`.
2. Review all date-based conditions across the course.
3. Identify restrictions ending before your course deadline.
4. Edit dates as needed.

**Scenario 2: Remove all group-based restrictions for a section**

1. Select the target **Section** from the dropdown.
2. Set **Restriction Type** filter to `Group`.
3. Click **Delete All Visible** (with confirmation).

**Scenario 3: Audit player progression gating**

1. If PlayerHUD is installed, set **Restriction Type** filter to `PlayerHUD`.
2. Review all level-based, item-based, and character-class restrictions.
3. Verify that the progression makes pedagogical sense.
