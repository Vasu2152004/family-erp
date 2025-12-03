FAMILY ERP SYSTEM – DETAILED PRODUCT REQUIREMENTS DOCUMENT (PRD)

------------------------------

0. INTRODUCTION
This PRD describes the complete functional requirements for a multi-tenant Family ERP System. It
includes modules for family management, finance, inventory, health, documents, tasks, assets, and
special rules such as hidden investments with password unlock and admin override.

------------------------------

1. FAMILY MEMBER MANAGEMENT
- Multi-tenant architecture: every family and member is associated with tenant_id.
- Family Member Fields: first_name, last_name, gender, DOB, relation, phone, email, is_deceased,
date_of_death.
- User Linking: family member may or may not be linked to a system login user.
- Roles: OWNER, ADMIN, MEMBER, VIEWER stored in family_user_roles.
- Backup Admin: OWNER can assign one or multiple backup admins.
- Admin Transfer Logic:
1) If OWNER dies → backup admin becomes OWNER automatically.
2) If no backup admin → users may request admin role.
3) User must request 3 times.
4) If still no response and no ADMIN/OWNER exists → system auto-promotes requester.

------------------------------

2. FINANCE & EXPENSE MANAGEMENT
- Finance Accounts: CASH, BANK, WALLET stored under family.
- Transactions Table:
- Type: INCOME, EXPENSE, TRANSFER
- Category-based grouping
- Linked to family_member_id
- Budget Planner:
- Monthly family budget
- Alerts when exceeded
- Analytics Dashboard:
- Monthly expense charts
- Member-wise spending distribution
- Permissions:
- OWNER/ADMIN: view all
- MEMBER: view own transactions unless allowed otherwise.

------------------------------

3. SHOPPING & INVENTORY TRACKING
- Inventory Items:
- name, category, qty, min_qty, expiry_date
- Low stock alerts
- Shopping List Module:
- Auto-add low stock items
- Users can mark purchased
- Multi-user real-time sync.

------------------------------

4. FAMILY CALENDAR & EVENTS
- Events for birthdays, anniversaries, doctor visits, school events.
- Reminders via push/email.
- Events stored with reminder_before_minutes.
- Shared calendar for entire family.

------------------------------

5. DOCUMENT STORAGE MODULE
- Upload PDFs/images for:
- Aadhaar, PAN, Passport, Property papers, Insurance, Certificates
- Sensitive documents:
- Only OWNER, ADMIN, and that member can access.
- Auto-expiry reminders for:
- Passport
- Driving license
- Insurance

------------------------------

6. HEALTH & MEDICAL RECORDS
- Health Profile per family member:
- Blood group, allergies, chronic conditions.
- Medical Records:
- Visit logs, prescription, lab reports.
- Medicine Reminder:
- Daily/weekly schedule support.

------------------------------

7. HOUSEHOLD TASK / CHORE MANAGEMENT
- Create tasks:
- DAILY, WEEKLY, MONTHLY, or ONCE.
- Assign to members.
- Status lifecycle:
- PENDING → IN_PROGRESS → DONE.
- Task Logs maintain history.

------------------------------

8. FAMILY NOTES / DIARY
- Shared notes for family collaboration.
- Private notes:
- Visible only to creator + OWNER/ADMIN.
- Locked notes (optional):
- Require PIN to unlock.
- OWNER/ADMIN can bypass lock.

------------------------------

9. VEHICLES & MAINTENANCE TRACKING
- Vehicle Details:
- RC, insurance expiry, PUC expiry.
- Service Logs:
- Cost, odometer, service center details.
- Fuel Tracking:
- Mileage estimation.
- Alerts for expiry dates.

------------------------------

10. FAMILY ASSETS & INVESTMENTS (with HIDDEN MODE)
- Asset Types:
- Property, gold, jewelry, land, vehicles.
- Investment Types:
- FD, RD, SIP, Mutual Funds, Stocks, Crypto.
- Hidden Investments:
- User can mark investment as hidden.
- Hidden investments store details encrypted.
- Normal users must enter PIN to unlock.
- ADMIN/OWNER can bypass the password and view directly.
- Investment Access Logs:
- Tracks view, unlock attempts, and edits.

------------------------------
11. MULTI-TENANT ARCHITECTURE
- Uses shared database with tenant_id isolation.
- Middleware ensures user cannot access another tenant’s data.
- All top-level tables include tenant_id:
- families, family_members, transactions, assets, notes, etc.
------------------------------
12. SYSTEM DIAGRAM (TEXT FORM)
USER → Laravel Routes → Controllers → Policies → MySQL
|
→ Documents Storage
|
→ Scheduled Jobs (Reminders, Auto-transfer, Expiry Alerts)
------------------------------
13. SUMMARY
This PRD defines all core and advanced modules required in the Family ERP system with
multi-tenant support, admin emergency transfer rules, and encrypted hidden investments. It is
designed for Laravel + MySQL implementation with scalable future expansion.