# NoovaPOS — Claude Prompt Templates

A reference library of reusable prompts for fast, token-efficient development.
Paste, fill in the blanks, and send.

---

## HOW TO USE

- Replace `[Resource]`, `[Module]`, etc. with your actual names.
- Add "Follow NoovaPOS architecture from CLAUDE.md" to any prompt when context matters.
- Scope tightly — one class, one method, one bug at a time.

---

---

# LARAVEL BACKEND PROMPTS

---

## 1. Full Resource Scaffold

```
Generate a Laravel [Resource] with:
- Migration (table: [table_name], tenant-aware)
- Model (fillable, casts, relations, scopes)
- Repository Interface + Implementation (app/Repositories/)
- Service Class (app/Services/)
- API Resource (app/Http/Resources/)
- Form Request for store + update
- API Controller (index, store, show, update, destroy)
- Route registration in api.php

Follow NoovaPOS repository pattern and service layer architecture.
Tenant isolation must be applied.
```

---

## 2. Migration Only

```
Generate a Laravel migration for [table_name].

Columns:
- [list columns with types]

Requirements:
- tenant_id foreign key
- soft deletes
- indexes on [columns]
- timestamps

Follow NoovaPOS schema conventions.
```

---

## 3. Model Only

```
Generate the [ModelName] Eloquent model.

Table: [table_name]

Include:
- fillable array
- casts (dates, json, enums)
- relations: [list relations]
- local scopes: [list scopes if any]
- tenant isolation (global scope or tenancy)

No migration. Model only.
```

---

## 4. Repository Pattern

```
Generate the Repository for [ModelName].

Interface: app/Repositories/Contracts/[ModelName]RepositoryInterface.php
Implementation: app/Repositories/[ModelName]Repository.php

Methods needed:
- [list methods, e.g. findByTenant, paginateWithFilters, findByBarcode]

Use Eloquent. Follow NoovaPOS repository pattern.
Bind in AppServiceProvider.
```

---

## 5. Service Class

```
Generate the Service class for [FeatureName].

File: app/Services/[FeatureName]Service.php

Inject:
- [RepositoryInterface or other dependencies]

Methods:
- [list methods with brief description]

Business rules:
- [list any rules, e.g. check stock before sale, auto-post journal entry]

Throw domain exceptions on failure.
No controller logic inside service.
```

---

## 6. API Controller

```
Generate an API Controller for [Resource].

File: app/Http/Controllers/Api/[Resource]Controller.php

Methods: index, store, show, update, destroy
Use: [Resource]Service (injected)
Return: [Resource]Resource / [Resource]Collection
Apply: [middleware list, e.g. auth:sanctum, permission:manage-products]

index must support:
- search (q param)
- filters ([list filter params])
- pagination (per_page param)
- sorting (sort_by, sort_dir)

No direct Eloquent in controller.
```

---

## 7. Form Request Validation

```
Generate FormRequest classes for [Resource]:

StoreRequest: app/Http/Requests/[Resource]/Store[Resource]Request.php
UpdateRequest: app/Http/Requests/[Resource]/Update[Resource]Request.php

Validation rules:
- [list fields and rules]

Authorization: return true (handled by middleware).
```

---

## 8. Laravel Event + Listener

```
Generate a Laravel Event and Listener pair.

Event: app/Events/[EventName].php
Properties: [list data the event carries]

Listener: app/Listeners/[ListenerName].php
Should implement: ShouldQueue
Queue: [queue name, e.g. default / notifications / accounting]

Action the listener performs:
- [describe what it does, e.g. auto-post journal entry, send notification]

Register both in EventServiceProvider.
```

---

## 9. Queued Job

```
Generate a queued Laravel Job.

File: app/Jobs/[JobName].php
Implements: ShouldQueue
Queue: [queue name]
Tries: 3
Backoff: [60, 120, 300] seconds

Constructor receives:
- [list parameters]

handle() should:
- [describe logic]

On failure: log to [table or channel].
```

---

## 10. API Resource / Collection

```
Generate API Resource and Collection for [ModelName].

Resource: app/Http/Resources/[ModelName]Resource.php
Collection: app/Http/Resources/[ModelName]Collection.php

Fields to expose:
- [list fields]

Conditionally include:
- [list relations to include with whenLoaded]

Format dates as: Y-m-d H:i:s
```

---

## 11. Policy

```
Generate a Laravel Policy for [ModelName].

File: app/Policies/[ModelName]Policy.php

Methods: viewAny, view, create, update, delete, restore
Base rule: user must belong to same tenant
Additional rules:
- [list extra rules per method]

Register in AuthServiceProvider.
```

---

## 12. Scope / Query Filter

```
Generate a reusable QueryFilter class for [ModelName].

File: app/Filters/[ModelName]Filter.php

Supported filters:
- search (searches: [list columns])
- [other filter params]
- date_from / date_to (on [column])
- status
- sort_by / sort_dir

Apply via model scope: scopeFilter($query, $filters)
```

---

## 13. Database Seeder

```
Generate a seeder for [ModelName].

File: database/seeders/[ModelName]Seeder.php

Create [N] records using [ModelName]Factory.
Make it tenant-aware (pass tenant_id).

Also generate the Factory:
File: database/factories/[ModelName]Factory.php

Realistic fake data using Faker.
```

---

## 14. Scheduled Command

```
Generate an Artisan command for [task description].

File: app/Console/Commands/[CommandName].php
Signature: [signature]
Description: [description]

Logic:
- [describe what it does]

Schedule it in Kernel.php:
- Frequency: [e.g. daily at midnight, every 15 minutes]

Log output to: storage/logs/[command].log
```

---

## 15. Redis Caching Layer

```
Add Redis caching to [Service or Repository method].

Cache key pattern: [tenant_id]:[resource]:[identifier]
TTL: [seconds]
Tags: [[tag1], [tag2]]

Cache on: read
Invalidate on: write / update / delete

Use Cache::tags()->remember() pattern.
Show before/after the method.
```

---

---

# REACT FRONTEND PROMPTS

---

## 16. React Page Component

```
Generate a React page component for [FeatureName].

File: resources/js/pages/[Module]/[FeatureName].jsx

Features:
- Fetch data from API: [endpoint]
- Show loading skeleton
- Show empty state
- Show error state
- [list other UI requirements]

Use:
- Axios (via api.js utility)
- TailwindCSS
- NoovaPOS design system classes
- [Redux/Pinia/Zustand] for state if needed

No inline styles.
```

---

## 17. Reusable UI Component

```
Generate a reusable React component: [ComponentName]

File: resources/js/components/[ComponentName].jsx

Props:
- [list props with types and defaults]

Behavior:
- [describe what it does]

Style with TailwindCSS.
Export as default.
Include PropTypes or TypeScript types.
```

---

## 18. Custom React Hook

```
Generate a custom React hook: use[HookName]

File: resources/js/hooks/use[HookName].js

Purpose: [describe what it manages]

Returns:
- [list returned values and functions]

Uses:
- useState / useEffect / useCallback / useMemo as needed
- Axios for API calls if needed

Handle loading, error, and success states.
```

---

## 19. Redux Slice (or Zustand Store)

```
Generate a [Redux Toolkit slice / Zustand store] for [FeatureName].

File: resources/js/store/[featureName]Slice.js

State shape:
- [list state fields]

Actions / reducers:
- [list actions]

Async thunks:
- [list API calls, e.g. fetchProducts, createSale]

Use createAsyncThunk for API calls.
Handle pending / fulfilled / rejected states.
```

---

## 20. API Service (Axios)

```
Generate an Axios API service for [Module].

File: resources/js/services/[module]Service.js

Base URL from: import api from '@/utils/api'

Methods:
- [list: getAll(params), getById(id), create(data), update(id, data), delete(id)]

Each method returns the axios promise.
No try/catch inside — let the caller handle errors.
```

---

## 21. Data Table Component

```
Generate a data table component for [Resource].

File: resources/js/components/[Resource]Table.jsx

Features:
- Columns: [list columns]
- Server-side pagination
- Search input (debounced 300ms)
- Sort by column
- Row actions: [edit, delete, view, etc.]
- Loading skeleton rows
- Empty state message

Props:
- data, pagination, onPageChange, onSearch, onSort, isLoading

Use TailwindCSS. Follow NoovaPOS enterprise table design.
```

---

## 22. Modal / Dialog Component

```
Generate a Modal component for [Create/Edit] [Resource].

File: resources/js/components/[Resource]Modal.jsx

Props:
- isOpen, onClose, onSuccess, [editData (optional for edit mode)]

Form fields:
- [list fields with types: text, select, date, etc.]

On submit:
- Call API: [POST/PUT] [endpoint]
- Show loading on button
- On success: call onSuccess(), close modal
- On error: show field-level validation errors from Laravel

Use TailwindCSS + NoovaPOS modal styles.
```

---

## 23. POS Cart Logic

```
Work on the POS cart.

File: resources/js/store/cartSlice.js (or useCart.js)

Method to fix/add: [method name]

Current code:
[paste current method]

Required behavior:
- [describe what it should do]
- [any business rules, e.g. max discount, stock check]

Change only this method. Keep everything else intact.
```

---

## 24. Search with Meilisearch

```
Generate a debounced search component using Meilisearch.

File: resources/js/components/[Resource]Search.jsx

API endpoint: GET /api/[resource]/search?q=

Features:
- Debounce: 300ms
- Min chars: 2
- Show dropdown results
- Keyboard navigation (arrow keys + enter)
- On select: [describe what happens]
- Loading spinner while fetching
- Empty message if no results

Use TailwindCSS.
```

---

---

# TESTING PROMPTS

---

## 25. Laravel Feature Test

```
Write a PHPUnit feature test for [ControllerMethod or Feature].

File: tests/Feature/[Resource]/[TestName]Test.php

Test cases:
- Success case: [describe]
- Validation failure: [describe]
- Unauthorized (no token)
- Forbidden (wrong role/permission)
- [any edge case]

Use: RefreshDatabase, actingAs(user with role)
Seed required data in setUp().
Assert: status code, JSON structure, database state.
```

---

## 26. Laravel Unit Test (Service/Repository)

```
Write a PHPUnit unit test for [ServiceName or RepositoryName].

File: tests/Unit/[ClassName]Test.php

Method to test: [method name]

Test cases:
- [list scenarios]

Mock dependencies using Mockery.
No database hits — pure unit test.
Assert return values and side effects.
```

---

## 27. React Component Test (Vitest)

```
Write Vitest + React Testing Library tests for [ComponentName].

File: resources/js/__tests__/[ComponentName].test.jsx

Test cases:
- Renders correctly
- [list interactions to test, e.g. user types in search, clicks submit]
- Shows loading state
- Shows error state
- [any specific behavior]

Mock API calls with vi.mock.
Use userEvent for interactions.
```

---

## 28. React Hook Test

```
Write Vitest tests for the custom hook: use[HookName]

File: resources/js/__tests__/hooks/use[HookName].test.js

Test cases:
- Initial state is correct
- [list state changes to test]
- API call is made with correct params
- Error state is set on API failure

Use renderHook from @testing-library/react.
Mock axios with vi.mock.
```

---

---

# DEBUGGING PROMPTS

---

## 29. Fix a Bug

```
Bug in [FileName] — method [methodName].

Error:
[paste exact error message or unexpected behavior]

Current code:
[paste only the broken method/block]

Expected behavior:
[describe what it should do]

Fix only the broken part. Show diff-style changes (before/after).
Do not rewrite the whole file.
```

---

## 30. Performance Issue

```
Performance problem in [file or query].

Issue: [describe — slow query, N+1, re-render, etc.]

Current code:
[paste the slow code]

Context:
- Dataset size: [e.g. 100k products]
- Current response time: [Xms]

Optimize for: [speed / memory / both]
Show the optimized version with explanation of what changed.
```

---

## 31. Refactor for Patterns

```
Refactor [FileName] to follow NoovaPOS architecture.

Issues:
- [e.g. business logic in controller, direct Eloquent in controller, no caching]

Current code:
[paste code]

Apply:
- Service layer
- Repository pattern
- Proper event firing
- [other patterns needed]

Show the refactored version split into correct files.
```

---

---

# SCHEMA & MIGRATION PROMPTS

---

## 32. Schema Design

```
Design the database schema for [Feature/Module].

Requirements:
- [list what it needs to store]
- Must be tenant-aware
- Must support [soft deletes / audit trail / versioning]

Output:
- Table names
- Columns with types
- Foreign keys
- Indexes
- Any pivot tables

Do not generate migration code yet — schema design only.
```

---

## 33. Index Optimization

```
Review and optimize indexes for [table_name].

Current columns:
[list columns]

Query patterns (most common):
- [e.g. filter by tenant_id + status + created_at]
- [e.g. search by customer_id]

Suggest:
- Which composite indexes to add
- Which single indexes
- Any to remove
- Any to convert to fulltext

Explain why for each.
```

---

---

# QUEUE & JOB PROMPTS

---

## 34. Import Job (Chunked)

```
Generate a chunked import job for [Resource].

Upload handler: app/Http/Controllers/Api/[Resource]ImportController.php
Job: app/Jobs/Process[Resource]ImportChunk.php

Flow:
1. Upload CSV
2. Create import_job record
3. Split into chunks of [N] rows
4. Dispatch one job per chunk
5. Each job processes rows + updates import_job_items
6. Frontend polls: GET /api/import-jobs/{id}/progress

Handle:
- Validation errors per row (store in import_job_items)
- Failed row CSV download
- Retry failed rows only

Follow NoovaPOS import engine architecture.
```

---

## 35. Horizon Queue Config

```
Generate the Laravel Horizon configuration for NoovaPOS.

Queues needed:
- default (general jobs)
- pos (sale processing — high priority)
- imports (chunked import workers)
- notifications (email/SMS/WhatsApp)
- accounting (journal auto-posting)
- fbr (FBR invoice sync)
- sync (offline sync)

For each queue define:
- worker count
- timeout
- memory limit
- retry limit
- balance strategy

Environment: production
```

---

---

# QUICK ONE-LINERS

---

```
Add soft deletes to [ModelName] + migration.
```

```
Add [column] to [table] — migration + model update only.
```

```
Generate [ModelName]Factory with realistic Faker data.
```

```
Add [permission_name] permission check to [ControllerMethod].
```

```
Add Redis cache tag invalidation when [ModelName] is updated.
```

```
Generate the API route group for [Module] inside routes/api.php.
```

```
Convert [ControllerMethod] to use cursor pagination instead of offset.
```

```
Add activity log (spatie/laravel-activitylog) to [ModelName] model.
```

```
Generate a Meilisearch index config for [ModelName].
```

```
Add rate limiting to [route group] — [N] requests per minute per tenant.
```

---

---

*Keep this file updated as your patterns evolve.*
*The more specific your prompt, the faster and cheaper Claude responds.*
