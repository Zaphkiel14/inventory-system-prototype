# Graph Report - inventory-system-prototype  (2026-11-26)

## Corpus Check
- 330 files · ~100,834 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 18, .css 2, .example 1)

## Summary
- 2311 nodes · 4570 edges · 278 communities (78 shown, 200 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 113 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `090b9ae7`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ApprovalsTest.php
- Profile
- Frontend NPM Dependencies
- SuppliersForm
- AdjustmentType
- static
- StockStatusMonitor
- InventoryBatchesFactory.php
- StockReconciliation
- RequisitionStatusUpdated
- Illuminate\Support\Collection
- InventoryItemsForm
- Reports Aggregation
- Illuminate\Database\Eloquent\Relations\HasMany
- Illuminate\Database\Eloquent\Builder
- AdministrativeAreaService.php
- PartialDeliveryTest.php
- Illuminate\Database\Schema\Blueprint
- StockReconciliationTest.php
- TrackRequest
- App\Models\InventoryItems
- Stocks
- DeliveryTimelineTest.php
- StockMonitoring
- RequisitionStatus
- App\Livewire\Monitoring\Reports
- Attachments.php
- ExpirationMonitoring
- StockReconciliation
- PasswordValidationRules
- StockReceiptsForm
- User
- chart.js
- Issuances
- DashboardTest.php
- Illuminate\Database\Eloquent\Collection
- Dashboard
- Composer Scripts
- Requisitions.php
- ReceivingCreateForm
- StockReconciliation
- Composer Metadata
- Composer Dev Dependencies
- App\Models\StockReceipts
- Deploy Workflow
- App\Models\InventoryBatches
- Security Blade View
- HeaderTest.php
- InventoryTransactions
- RequestsTest.php
- StockStatusAlertsTest.php
- ReportsTest.php
- TransactionHistoryTest.php
- receiving.blade.php
- Requests
- bootstrap/app.php
- Composer Runtime Deps
- .view
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Items
- requests.blade.php
- RequisitionsForm
- TelescopeServiceProvider.php
- Composer Config
- Livewire\Component
- receiveRemaining
- approvals.blade
- composer
- composer (2)
- App\Models\StorageLocations
- table
- InventoryCategories
- stock-monitoring.blade
- AppServiceProvider.php
- expiration-monitoring.blade
- BatchCodeGenerator
- table (4)
- Team Agents
- table (6)
- table (7)
- table (8)
- table (9)
- table (10)
- table (11)
- table (12)
- table (13)
- table (14)
- table (15)
- Suppliers
- transaction-history.blade
- profile.blade
- bootstrap.blade
- simple-bootstrap.blade
- simple-tailwind.blade
- tailwind.blade
- IssuancesTest.php
- RecoveryCodes
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- App\Models\Approvals
- Database\Factories\InventoryAdjustmentsFactory
- 2026_11_26_143822_create_telescope_entries_table.php
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- partials.head
- card.blade
- simple.blade
- split.blade
- App\Models\InventoryAdjustments
- request-form.blade.php
- storage-locations.blade.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- categories.blade
- Illuminate\Database\Eloquent\Factories\Factory
- appearance.blade
- Requisitions
- Requests
- InventoryItems
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- Logout.php
- public-portal.blade.php
- FortifyServiceProvider
- guest.blade.php
- App\Enums\RequisitionStatus
- CategoryMultiSelect
- DonationReceivingTest.php
- Suppliers
- Receiving.php
- InventoryBatches
- Pest.php
- InventoryCategoriesForm
- App\Models\Suppliers
- Security
- Illuminate\Database\Seeder
- monitoring/stock-reconciliation.blade.php
- MailTemplatesTest.php
- 2026_11_16_071730_create_suppliers_table.php
- 0001_01_01_000002_create_jobs_table.php
- StockStatus
- Approvals
- DatabaseSeeder.php
- .apply
- ProfileValidationRules
- RequestEmailNotificationsTest.php
- TrackRequestTest.php
- 2026_11_16_081104_create_inventory_categories_table.php
- App\Support\MailLogo
- PublicPortal

## God Nodes (most connected - your core abstractions)
1. `dtRoot()` - 37 edges
2. `InventoryItems` - 34 edges
3. `AdjustmentType` - 34 edges
4. `RequisitionStatus` - 29 edges
5. `StockReceipts` - 29 edges
6. `User` - 28 edges
7. `InventoryTransactionType` - 28 edges
8. `dtFollowUp()` - 26 edges
9. `Reports` - 25 edges
10. `Stocks` - 25 edges

## Surprising Connections (you probably didn't know these)
- `ledger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/ReportsTest.php → app/Models/InventoryTransactions.php
- `stockLedger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/StockMonitoringTest.php → app/Models/InventoryTransactions.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#11}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#5}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (278 total, 200 thin omitted)

### Community 0 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.09
Nodes (19): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#1}(), {closure#2}() (+11 more)

### Community 5 - "static"
Cohesion: 0.17
Nodes (3): InventoryAdjustmentsFactory, StockReceiptsFactory, UserFactory

### Community 6 - "StockStatusMonitor"
Cohesion: 0.24
Nodes (3): CheckStockStatus, {closure#2}(), StockStatusMonitor

### Community 8 - "InventoryBatchesFactory.php"
Cohesion: 0.15
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory

### Community 9 - "StockReconciliation"
Cohesion: 0.10
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#8}(), {closure#9}() (+1 more)

### Community 10 - "RequisitionStatusUpdated"
Cohesion: 0.19
Nodes (5): RequisitionStatusUpdated, StockStatusAlert, {closure#15}(), {closure#4}(), {closure#7}()

### Community 12 - "InventoryItemsForm"
Cohesion: 0.16
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 15 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.05
Nodes (36): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+28 more)

### Community 16 - "AdministrativeAreaService.php"
Cohesion: 0.10
Nodes (7): {closure#10}(), {closure#13}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}(), {closure#9}()

### Community 17 - "PartialDeliveryTest.php"
Cohesion: 0.09
Nodes (40): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+32 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+1 more)

### Community 19 - "StockReconciliationTest.php"
Cohesion: 0.15
Nodes (32): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+24 more)

### Community 22 - "App\Models\InventoryItems"
Cohesion: 0.09
Nodes (22): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+14 more)

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.08
Nodes (43): ReceiptStatus, Closed, Complete, Partial, {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}() (+35 more)

### Community 26 - "RequisitionStatus"
Cohesion: 0.17
Nodes (19): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, assertStatusMailSent() (+11 more)

### Community 27 - "App\Livewire\Monitoring\Reports"
Cohesion: 0.15
Nodes (6): {closure#10}(), {closure#2}(), {closure#7}(), {closure#8}(), {closure#9}(), {closure#26}()

### Community 31 - "PasswordValidationRules"
Cohesion: 0.21
Nodes (4): CreateNewUser, ResetUserPassword, PasswordValidationRules, DeleteUserForm

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 37 - "DashboardTest.php"
Cohesion: 0.14
Nodes (29): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#19}() (+21 more)

### Community 38 - "Illuminate\Database\Eloquent\Collection"
Cohesion: 0.16
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Requisitions.php"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#1}(), {closure#2}()

### Community 43 - "StockReconciliation"
Cohesion: 0.27
Nodes (4): {closure#1}(), {closure#22}(), {closure#8}(), StockReconciliation

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.15
Nodes (13): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+5 more)

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 49 - "App\Models\InventoryBatches"
Cohesion: 0.13
Nodes (11): {closure#22}(), {closure#23}(), {closure#1}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#1}() (+3 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "HeaderTest.php"
Cohesion: 0.15
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 52 - "InventoryTransactions"
Cohesion: 0.20
Nodes (3): InventoryTransactionsForm, {closure#7}(), InventoryTransactions

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "StockStatusAlertsTest.php"
Cohesion: 0.24
Nodes (14): alertBaseline(), alertBatch(), alertIssue(), alertItem(), alertReceive(), alertsSentTo(), {closure#11}(), {closure#12}() (+6 more)

### Community 55 - "ReportsTest.php"
Cohesion: 0.14
Nodes (14): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#10}(), {closure#2}(), {closure#4}() (+6 more)

### Community 56 - "TransactionHistoryTest.php"
Cohesion: 0.26
Nodes (8): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyEntry(), historyIds()

### Community 57 - "receiving.blade.php"
Cohesion: 0.33
Nodes (5): closeDelivery, confirmClose, livewire.inventory.partials.receipt-details, receiveRemaining, view

### Community 59 - "bootstrap/app.php"
Cohesion: 0.23
Nodes (6): {closure#10}(), {closure#8}(), {closure#9}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux (+2 more)

### Community 61 - ".view"
Cohesion: 0.21
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 64 - "Items"
Cohesion: 0.09
Nodes (3): StorageLocations, Categories, Items

### Community 65 - "requests.blade.php"
Cohesion: 0.50
Nodes (3): cancel, livewire.requisitions.partials.requester-details, view

### Community 68 - "TelescopeServiceProvider.php"
Cohesion: 0.24
Nodes (3): {closure#1}(), {closure#2}(), TelescopeServiceProvider

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "Livewire\Component"
Cohesion: 0.07
Nodes (6): AuditLogs, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), Appearance

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 80 - "InventoryCategories"
Cohesion: 0.20
Nodes (3): {closure#2}(), InventoryCategories, InventoryCategoriesFactory

### Community 82 - "stock-monitoring.blade"
Cohesion: 0.40
Nodes (4): livewire.monitoring.partials.stock-detail, clearFilters, filterStatus, toggle

### Community 84 - "expiration-monitoring.blade"
Cohesion: 0.50
Nodes (3): confirmWriteOff, clearFilters, filterStatus

### Community 87 - "Team Agents"
Cohesion: 0.10
Nodes (19): `.claude/agents/coder.md`, `.claude/agents/designer.md`, `.claude/agents/orchestrator.md`, `.claude/agents/tester.md`, Codebases, Driving the team, Enable it, How We Use Claude (+11 more)

### Community 99 - "transaction-history.blade"
Cohesion: 0.50
Nodes (3): export, clearFilters, toggleOrder

### Community 101 - "profile.blade"
Cohesion: 0.50
Nodes (3): partials.settings-heading, resendVerificationNotification, settings.delete-user-form

### Community 102 - "bootstrap.blade"
Cohesion: 0.50
Nodes (3): gotoPage, nextPage, previousPage

### Community 103 - "simple-bootstrap.blade"
Cohesion: 0.50
Nodes (3): nextPage, previousPage, setPage

### Community 104 - "simple-tailwind.blade"
Cohesion: 0.50
Nodes (3): nextPage, previousPage, setPage

### Community 105 - "tailwind.blade"
Cohesion: 0.50
Nodes (3): gotoPage, nextPage, previousPage

### Community 106 - "IssuancesTest.php"
Cohesion: 0.05
Nodes (28): StorageLocationForm, StorageLocationsForm, {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+20 more)

### Community 109 - "issuances.blade"
Cohesion: 0.40
Nodes (4): confirmRelease, open, release, livewire.requisitions.partials.requester-details

### Community 114 - "Database\Factories\InventoryAdjustmentsFactory"
Cohesion: 0.32
Nodes (3): {closure#1}(), {closure#2}(), {closure#4}()

### Community 115 - "2026_11_26_143822_create_telescope_entries_table.php"
Cohesion: 0.38
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), down(), getConnection(), up()

### Community 124 - "App\Models\InventoryAdjustments"
Cohesion: 0.11
Nodes (7): InventoryAdjustments, StockIssuances, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), TransactionReferences

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.20
Nodes (7): configurations.storage-location-form, delete, editViewing, inventory.supplier-form, partials.configuration-heading, sort, toggleActive

### Community 127 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.15
Nodes (4): AuditLogs, RequisitionItems, StockIssuanceBatches, StockReceiptLines

### Community 198 - "Requisitions"
Cohesion: 0.16
Nodes (5): ApprovalsForm, {closure#2}(), {closure#3}(), {closure#1}(), Requisitions

### Community 200 - "InventoryItems"
Cohesion: 0.16
Nodes (7): {closure#1}(), {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator, {closure#1}()

### Community 211 - "App\Enums\RequisitionStatus"
Cohesion: 0.14
Nodes (3): {closure#2}(), {closure#1}(), {closure#1}()

### Community 218 - "DonationReceivingTest.php"
Cohesion: 0.07
Nodes (31): {closure#10}(), {closure#11}(), {closure#12}(), {closure#19}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}() (+23 more)

### Community 228 - "Receiving.php"
Cohesion: 0.07
Nodes (3): {closure#10}(), {closure#2}(), Receiving

### Community 240 - "Illuminate\Database\Seeder"
Cohesion: 0.17
Nodes (5): StorageLocations, InventoryCategoriesSeeder, InventoryItemsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 242 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.25
Nodes (6): clearBatch, clearFilters, clearReceipt, exportPdf, showExceptions, toggleSortDirection

### Community 252 - "MailTemplatesTest.php"
Cohesion: 0.20
Nodes (9): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#9}() (+1 more)

### Community 260 - "0001_01_01_000002_create_jobs_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 266 - "StockStatus"
Cohesion: 0.14
Nodes (7): StockStatus, Critical, Healthy, Out, Reorder, {closure#4}(), {closure#5}()

### Community 271 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 272 - "TrackRequestTest.php"
Cohesion: 0.11
Nodes (3): ApprovalDecision, Approved, Rejected

## Knowledge Gaps
- **106 isolated node(s):** `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+101 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 904 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **200 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Laravel\Fortify\Features`, `Illuminate\Database\Eloquent\Factories\Factory`, `PasswordResetTest.php`, `EmailVerificationTest.php`, `Requisitions`, `Livewire\Component`, `StockStatus`, `DatabaseSeeder.php`, `ProfileUpdateTest.php`, `PasswordValidationRules`, `SecurityTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **What connects `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form` to the rest of the system?**
  _106 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `StockReconciliation` connect `StockReconciliation` to `PublicPortal`, `StockReconciliationTest.php`, `StockReconciliation`, `Livewire\Component`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.09274193548387097 - nodes in this community are weakly interconnected._
- **Why does `Approvals` connect `Approvals` to `ApprovalsTest.php`, `Requests`, `Livewire\Component`, `Requisitions.php`, `Illuminate\Database\Eloquent\Builder`, `App\Enums\RequisitionStatus`, `RequisitionStatus`, `.view`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **Should `StockReconciliation` be split into smaller, more focused modules?**
  _Cohesion score 0.09852216748768473 - nodes in this community are weakly interconnected._