You are a principal software architect, enterprise SaaS engineer, UI/UX architect, DevOps architect, and Laravel performance expert.

Your task is to design and generate production-grade architecture, code structure, APIs, modules, database schema, workflows, frontend structure, queue systems, offline architecture, and enterprise UI plans for a complete SaaS POS + ERP platform named:

# NoovaPOS

====================================================
PROJECT OVERVIEW
====================================================

NoovaPOS is a multi-tenant SaaS POS + ERP platform built using:

Backend:
- Laravel Latest
- PHP 8+
- MySQL 8
- Redis
- Queue Workers
- Stancl Tenancy
- Sanctum Authentication

Frontend:
- React/Vue
- Redux/Pinia
- Tailwind CSS
- Responsive Enterprise UI
- PWA Support

Infrastructure:
- Redis
- Meilisearch
- S3
- Cloudflare
- WebSockets
- Horizon
- Supervisor
- Docker
- CI/CD

====================================================
SYSTEM GOAL
====================================================

Build a complete enterprise-grade SaaS ecosystem where one platform supports multiple business types.

Hierarchy:

Platform Super Admin
    → Tenants / Companies
        → Stores / Branches
            → Shops / POS Counters
                → Registers
                    → Users / Employees

====================================================
IMPORTANT REQUIREMENTS
====================================================

The system must be:

- Enterprise-grade
- SaaS-ready
- Offline-first
- Multi-tenant
- Modular
- Event-driven
- Queue-driven
- API-first
- Highly scalable
- Redis optimized
- Meilisearch optimized
- Mobile/tablet friendly
- Keyboard optimized
- Virtualized rendering ready
- Background processing enabled
- FBR Pakistan ready
- Multi-business-type capable

====================================================
MULTI BUSINESS TYPES
====================================================

Each tenant can buy different shop/business modules.

Supported business types:

- Retail POS
- Restaurant POS
- Pharmacy POS
- Bakery POS
- Water Supply POS
- Distribution POS
- Warehouse POS
- Electronics POS
- Fashion Store POS
- Monthly Service POS
- Subscription Billing POS
- Custom Business Type

Example:

Tenant:
ABC Group

Owns:
- 2 Retail Shops
- 1 Restaurant
- 1 Water Delivery Branch

Each shop has isolated features/modules.

====================================================
SHOP TYPE FEATURE ISOLATION
====================================================

Retail Shop:
- barcode
- inventory
- warehouse
- GRN
- stock transfer
- supplier management

Restaurant Shop:
- tables
- halls
- KOT
- kitchen display
- waiter app
- split bills
- dine-in

Water Supply Shop:
- recurring delivery
- bottle tracking
- route management
- delivery scheduling
- deposit tracking

Monthly Service POS:
- recurring invoices
- subscriptions
- billing cycles
- overdue tracking

====================================================
MULTI-TENANT ARCHITECTURE
====================================================

Use Stancl Tenancy.

Each tenant must support:

- subdomain
- custom domain
- isolated data
- tenant settings
- tenant themes
- tenant modules

Examples:

company1.noovapos.com
restaurant.noovapos.com

Wildcard DNS required.

====================================================
ROLES & PERMISSIONS
====================================================

Roles:

- platform_super_admin
- admin
- tenant_owner
- branch_manager
- shop_manager
- cashier
- waiter
- kitchen              (restaurant back-of-house: KOT queue / kitchen display)
- accountant
- inventory_manager
- delivery_staff       (water / distribution driver)
- delivery_boy         (restaurant takeaway/delivery rider)

Requirements:

- Dynamic permissions
- Role inheritance
- Shop-level permissions
- Store-level restrictions
- Module-level access control

====================================================
SUBSCRIPTION SYSTEM
====================================================

Build complete SaaS subscription engine.

Features:

- Free trial
- Monthly/yearly plans
- Feature limits
- Shop limits
- User limits
- Storage limits
- API limits
- Add-on purchases
- Module purchases
- Upgrade/downgrade
- Plan expiration
- Tenant suspension

Plan features:

- FBR module (fbr only for pakistan )
- Offline mode with FBR (fbr only for pakistan )
- Accounting
- AI reports
- WhatsApp integration (use free mothed)
- CRM
- HRM

====================================================
SUPER ADMIN PANEL
====================================================

Platform owner dashboard must include:

- Total tenants
- MRR analytics
- Revenue charts
- Active subscriptions
- Queue health
- Import statistics
- FBR sync success rate
- Active devices
- Offline terminals
- Failed jobs
- Tenant growth analytics
- System health
- Redis metrics
- Queue metrics
- Websocket metrics

Super admin can:

- Suspend tenant
- Impersonate tenant
- Force logout users
- Reset subscriptions
- Manage plans
- Manage modules
- Monitor imports
- Retry failed jobs
- View audit logs

====================================================
POS CORE REQUIREMENTS
====================================================

POS must support:

- Barcode scanning
- Fast cart system
- Multi-payment
- Split payment
- Credit sales
- Refunds
- Returns
- Hold sales
- Quotations
- Receipt printing
- QR invoices
- Keyboard shortcuts
- Touch optimization

Keyboard shortcuts:

F1 = Search
F2 = Customer
F4 = Hold
F8 = Payment
Ctrl+Enter = Complete Sale

====================================================
POS PERFORMANCE REQUIREMENTS
====================================================

CRITICAL:

System must support:

- 100k+ products
- thousands of sales/day
- multiple terminals
- large tenants

Use:

- Virtualized rendering
- Lazy loading
- Local indexed cache
- Redis caching
- Debounced search
- Server-side pagination
- Cursor pagination
- Meilisearch

DO NOT render huge product lists in DOM.

Use:
- react-window
- react-virtualized
OR equivalent

====================================================
OFFLINE-FIRST POS
====================================================

Build offline-first architecture.

Requirements:

- PWA
- IndexedDB
- SQLite (Electron mode)
- Background sync
- Sync queue
- Retry engine
- Idempotency protection
- Conflict handling
- Local sales cache
- Local product cache

Offline flow:

1. Sale created offline
2. Stored locally
3. local_uuid generated
4. Sync queue created
5. Background sync uploads
6. Server validates idempotency
7. Marks synced

====================================================
IMPORT ENGINE
====================================================

Imports must be enterprise-grade.

Requirements:

- Queue-based imports
- Chunk processing
- Background workers
- Retry failed rows
- Download failed CSV
- Progress tracking
- Resume after refresh
- Continue after logout
- Continue after browser close

Tables:

import_jobs
import_job_items

Architecture:

1. Upload file
2. Create import job
3. Dispatch chunk jobs
4. Worker processes chunks
5. Frontend polls progress

====================================================
ACCOUNTING MODULE
====================================================

Build full ERP accounting system.

Features:

- Chart of accounts
- Journal entries
- Trial balance
- Profit/loss
- Balance sheet
- Customer ledger
- Vendor ledger
- Expense tracking
- Bank reconciliation
- Auto-posting

Auto-post from:
- sales
- purchases
- expenses
- refunds

====================================================
INVENTORY SYSTEM
====================================================

Inventory architecture must be event-driven.

Use stock_movements as source of truth.

Features:

- warehouses
- transfers
- GRN
- purchase orders
- adjustments
- stock ledger
- serial numbers
- batches
- expiry tracking
- low stock alerts

Stock movement types:

- purchase
- sale
- return
- transfer
- adjustment

====================================================
FBR PAKISTAN INTEGRATION (User have option to enable/disable)
====================================================

Build complete FBR DI architecture.

Requirements:

- Sandbox mode
- Production mode
- Invoice queue
- Retry failed invoices
- QR codes
- FBR logs
- Pending sync dashboard
- Queue workers

Store:

- NTN
- STRN
- province
- business activity
- POS ID
- sandbox token
- production token

IMPORTANT:
Production architecture must support licensed FBR integrator workflow.

====================================================
WATER DELIVERY MODULE
====================================================

Build enterprise recurring water supply system.

Requirements:

- recurring deliveries
- selected delivery days
- monthly billing
- route assignment
- delivery tracking
- extra bottle requests
- skipped deliveries
- deposits
- bottle returns
- reusable inventory

Example:

Customer:
Ali

Delivery:
Monday/Wednesday/Friday

Default:
2 bottles/day

Extra Friday:
4 bottles

System auto-generates:
- delivery schedule
- invoices
- due reminders

====================================================
RESTAURANT MODULE
====================================================

Features:

- halls
- tables
- waiter assignment
- KOT
- kitchen display
- order queue
- split bills
- dine-in
- takeaway
- delivery

KOT states:

- open
- sent
- cooking
- ready
- served

====================================================
AI FEATURES (donot use any api like chatgbt)
====================================================

AI modules:

- sales prediction
- stock prediction
- reorder suggestions
- customer trends
- AI reporting
- anomaly detection
- smart accounting insights

====================================================
NOTIFICATION SYSTEM (free mothed if not make like if user for api keys)
====================================================

Support:

- email
- SMS
- WhatsApp
- push notifications
- in-app notifications

Use queue-based architecture.

====================================================
ENTERPRISE UI/UX
====================================================

Design style:

- modern SaaS ERP
- glassmorphism
- soft shadows
- dark/light mode
- analytics-first
- enterprise tables
- tablet-friendly
- touch-friendly
- dense data grids

Build CSS design system using variables.

Include:

- cards
- sidebars
- tables
- POS layout
- scrollbars
- widgets
- responsive breakpoints

====================================================
DATABASE REQUIREMENTS
====================================================

Generate scalable schema for:

- tenants
- domains
- stores
- shops
- registers
- users
- subscriptions
- plans
- modules
- stock_movements
- payments
- audit_logs
- notifications
- imports
- recurring_invoices
- delivery_schedules
- customer_subscriptions
- accounts
- journal_entries
- offline_devices
- sync_queue
- sync_batches
- fbr_profiles

====================================================
QUEUE & EVENT ARCHITECTURE
====================================================

Use:

- Laravel events
- listeners
- queued jobs
- Redis queues
- Horizon

Events:

- SaleCompleted
- PurchaseCompleted
- StockAdjusted
- InvoiceSynced
- SubscriptionExpired
- ImportCompleted

====================================================
API ARCHITECTURE
====================================================

Generate optimized API structure.

Separate APIs:

- platform APIs
- tenant APIs
- POS APIs
- offline APIs
- sync APIs
- analytics APIs

POS APIs must be ultra-lightweight.

====================================================
SECURITY REQUIREMENTS
====================================================

Implement:

- tenant isolation
- rate limiting
- queue protection
- audit logs
- permission middleware
- API throttling
- secure uploads
- XSS protection
- SQL injection protection
- activity tracking

====================================================
DEPLOYMENT ARCHITECTURE (use free mothed not any paid)
====================================================

Generate production deployment plan for:

- Docker
- Nginx
- Redis
- Horizon
- Supervisor
- Queue workers
- Cloudflare
- SSL
- S3 backups
- CI/CD

====================================================
OUTPUT REQUIRED
====================================================

Generate in detail:

1. Complete system architecture
2. SaaS tenancy architecture
3. Database schema
4. Laravel folder structure
5. React/Vue folder structure
6. API architecture
7. Queue architecture
8. Offline sync architecture
9. POS optimization strategy
10. Enterprise import system
11. Inventory architecture
12. Accounting architecture
13. Subscription engine
14. Delivery scheduling engine
15. Restaurant workflow
16. Water supply workflow
17. FBR integration workflow (for pakistanis)
18. Redis caching strategy
19. Meilisearch strategy
20. UI/UX structure
21. CSS design system
22. Server scaling strategy
23. Security architecture
24. Queue worker strategy
25. Backup strategy
26. Audit logging system
27. Notification architecture
28. AI architecture
29. Deployment architecture
30. DevOps strategy

IMPORTANT:

- Think like a senior enterprise SaaS architect.
- Use production-grade architecture.
- Prioritize scalability and maintainability.
- Avoid toy/demo implementations.
- Use modular architecture.
- Use service layer + repository pattern.
- Use event-driven design.
- Use background processing everywhere possible.
- Optimize for very large datasets.
- Design for future microservice migration.
- Keep APIs stateless and scalable.
- Make POS ultra-fast and resilient.
- Make imports/background tasks survive refresh/logout/browser close.

====================================================
IMPLEMENTATION STATUS (kept current — see README for detail)
====================================================

Recently built on top of the SaaS/billing/hierarchy core:

- Feature flags: global (Platform → Features) ∧ per-store (Settings → Store
  Features), resolved via FeatureService; UI gates on GET /api/my-features.
- POS cart: price-tier selector, Add Deal (combo → real product lines summing to
  the deal price), Mixed Units (box/pack/piece → base qty + unit_breakdown via
  GET /products/{id}/unit-levels), feature-gated Send to Kitchen with delta-KOT
  reprint (sends only newly added quantity).
- Subscription payments: two methods the super admin enables/disables —
  "request" (manual proof upload) and "checkout" (online). Checkout channels are
  country-aware: a global hosted payment LINK, or Pakistan locals (JazzCash,
  Easypaisa, HBL, Meezan, UBL, …). Managed at Platform → Billing Settings →
  Payment methods; hosted pay page at /billing/pay/{token}. No paid gateway SDK
  is required (free-method compliant) — a pending payment is recorded with the
  chosen channel + reference and confirmed by the super admin.

- Attendance Management (POS-integrated, reuses HR `employees`): kiosk check-in/out
  with Face (face-api.js, real client-side match), Barcode/QR (html5-qrcode +
  USB), Fingerprint (camera visual record, future-ready), Manual. Tasks
  (office/personal start/pause/resume/complete), breaks, dashboard + live board,
  reports (summary/productivity/performance), requests (employee correction →
  manager approve), and Configuration → Attendance. NO-CODE **Device Connectors**
  let any external scanner / face terminal / cloud API be added by config
  (endpoint + auth + request/response mapping), server- or client(localhost)-run,
  with no code changes. Permissions `attendance.*`; seeder
  AttendancePermissionSeeder.

Notes:
- FBR remains Pakistan-only; the FBR invoice number is assigned asynchronously by
  the FBR sync queue, so it appears on record/reprint after sync, not on the
  first instant slip.
- Frontend builds with Vite (rolldown). On Linux the native binding
  @rolldown/binding-linux-x64-gnu is required; Windows uses
  binding-win32-x64-msvc. Run `npm run build` in the OS you deploy from.
- Generate enterprise-level recommendations only.