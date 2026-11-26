# Graph Report - inventory-system-prototype  (2026-11-26)

## Corpus Check
- 327 files · ~99,570 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 18, .css 2, .example 1)

## Summary
- 2280 nodes · 4522 edges · 271 communities (71 shown, 200 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 113 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `75507bc1`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ApprovalsTest.php
- Frontend NPM Dependencies
- Illuminate\Database\Seeder
- AdjustmentType
- Issuances
- StockStatusMonitor
- InventoryBatchesFactory.php
- StockReconciliation
- RequisitionStatusUpdated
- Illuminate\Support\Collection
- InventoryTransactions
- Reports Aggregation
- StockIssuanceItems
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
- Suppliers
- StockReconciliation
- Illuminate\Database\Eloquent\Model
- ExpirationMonitoring
- Suppliers
- Security
- StockReceiptsForm
- User
- chart.js
- StockStatusAlert
- DashboardTest.php
- RequisitionItems
- Dashboard
- Composer Scripts
- Requisitions.php
- App\Livewire\Inventory\ReceivingCreateForm
- Inventory/StockReconciliation.php
- Composer Metadata
- Composer Dev Dependencies
- App\Models\StockReceipts
- Deploy Workflow
- App\Models\InventoryBatches
- Security Blade View
- HeaderTest.php
- App\Services\Inventory\BatchCodeGenerator
- RequestsTest.php
- StockStatusAlertsTest.php
- ReportsTest.php
- TransactionHistoryTest.php
- receiving.blade.php
- Carbon\CarbonInterface
- bootstrap/app.php
- Composer Runtime Deps
- .view
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Items
- requests.blade.php
- RequisitionsForm
- Composer Config
- Livewire\Component
- receiveRemaining
- approvals.blade
- composer
- composer (2)
- App\Models\StorageLocations
- table
- table (2)
- stock-monitoring.blade
- AppServiceProvider.php
- expiration-monitoring.blade
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
- item
- transaction-history.blade
- profile.blade
- bootstrap.blade
- simple-bootstrap.blade
- simple-tailwind.blade
- tailwind.blade
- IssuancesTest.php
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- App\Models\Approvals
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- partials.head
- card.blade
- simple.blade
- split.blade
- App\Models\StockIssuances
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
- Illuminate\Support\Collection
- public-portal.blade.php
- FortifyServiceProvider
- guest.blade.php
- App\Livewire\Dashboard
- CategoryMultiSelect
- DonationReceivingTest.php
- Receiving.php
- InventoryBatches
- App\Models\StorageLocations
- StockMonitoringTest.php
- PasswordValidationRules
- Pest.php
- monitoring/stock-reconciliation.blade.php
- MailTemplatesTest.php
- 2026_11_16_071730_create_suppliers_table.php
- App\Models\Suppliers
- StockStatus
- Approvals
- App\Livewire\Forms\ApprovalsForm
- ExpirationMonitoringTest.php
- RequestEmailNotificationsTest.php
- .request
- RecoveryCodes
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
- `{closure#2}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `ledger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/ReportsTest.php → app/Models/InventoryTransactions.php
- `stockLedger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/StockMonitoringTest.php → app/Models/InventoryTransactions.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (271 total, 200 thin omitted)

### Community 0 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "Illuminate\Database\Seeder"
Cohesion: 0.13
Nodes (6): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 4 - "AdjustmentType"
Cohesion: 0.07
Nodes (22): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, InventoryAdjustmentsFactory, StockReceiptsFactory (+14 more)

### Community 8 - "InventoryBatchesFactory.php"
Cohesion: 0.17
Nodes (3): {closure#2}(), {closure#3}(), InventoryBatchesFactory

### Community 9 - "StockReconciliation"
Cohesion: 0.10
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#8}(), {closure#9}() (+1 more)

### Community 10 - "RequisitionStatusUpdated"
Cohesion: 0.25
Nodes (3): RequisitionStatusUpdated, {closure#4}(), {closure#7}()

### Community 15 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.08
Nodes (28): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}() (+20 more)

### Community 16 - "AdministrativeAreaService.php"
Cohesion: 0.11
Nodes (6): {closure#10}(), {closure#13}(), {closure#2}(), {closure#3}(), {closure#7}(), {closure#9}()

### Community 17 - "PartialDeliveryTest.php"
Cohesion: 0.09
Nodes (40): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+32 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.15
Nodes (10): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+2 more)

### Community 19 - "StockReconciliationTest.php"
Cohesion: 0.15
Nodes (31): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+23 more)

### Community 22 - "App\Models\InventoryItems"
Cohesion: 0.08
Nodes (23): {closure#11}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+15 more)

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.09
Nodes (39): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+31 more)

### Community 26 - "Suppliers"
Cohesion: 0.17
Nodes (3): SuppliersForm, SupplierForm, Suppliers

### Community 29 - "ExpirationMonitoring"
Cohesion: 0.07
Nodes (7): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#5}(), {closure#8}(), ExpirationMonitoring, InventoryAdjustments

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 37 - "DashboardTest.php"
Cohesion: 0.08
Nodes (49): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#10}() (+41 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Requisitions.php"
Cohesion: 0.21
Nodes (4): {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 43 - "Inventory/StockReconciliation.php"
Cohesion: 0.09
Nodes (13): {closure#1}(), {closure#10}(), {closure#14}(), {closure#16}(), {closure#18}(), {closure#20}(), {closure#21}(), {closure#22}() (+5 more)

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 49 - "App\Models\InventoryBatches"
Cohesion: 0.14
Nodes (9): {closure#1}(), {closure#1}(), {closure#2}(), {closure#4}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}() (+1 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "HeaderTest.php"
Cohesion: 0.12
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "StockStatusAlertsTest.php"
Cohesion: 0.12
Nodes (19): {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), receivingForm(), alertBaseline(), alertBatch(), alertIssue() (+11 more)

### Community 55 - "ReportsTest.php"
Cohesion: 0.27
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 56 - "TransactionHistoryTest.php"
Cohesion: 0.31
Nodes (6): {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

### Community 57 - "receiving.blade.php"
Cohesion: 0.33
Nodes (5): closeDelivery, confirmClose, livewire.inventory.partials.receipt-details, receiveRemaining, view

### Community 58 - "Carbon\CarbonInterface"
Cohesion: 0.12
Nodes (14): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#2}(), {closure#7}(), {closure#8}(), {closure#9}() (+6 more)

### Community 59 - "bootstrap/app.php"
Cohesion: 0.23
Nodes (6): {closure#10}(), {closure#8}(), {closure#9}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux (+2 more)

### Community 61 - ".view"
Cohesion: 0.23
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 64 - "Items"
Cohesion: 0.08
Nodes (3): StorageLocations, Categories, Items

### Community 65 - "requests.blade.php"
Cohesion: 0.50
Nodes (3): cancel, livewire.requisitions.partials.requester-details, view

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "Livewire\Component"
Cohesion: 0.07
Nodes (6): InventoryItemsForm, ItemCreateForm, ItemEditForm, AuditLogs, Appearance, Profile

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

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
Cohesion: 0.21
Nodes (16): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+8 more)

### Community 109 - "issuances.blade"
Cohesion: 0.40
Nodes (4): confirmRelease, open, release, livewire.requisitions.partials.requester-details

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.20
Nodes (7): configurations.storage-location-form, delete, editViewing, inventory.supplier-form, partials.configuration-heading, sort, toggleActive

### Community 129 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.11
Nodes (6): InventoryCategories, StorageLocations, InventoryCategoriesFactory, {closure#1}(), InventoryItemsFactory, StorageLocationsFactory

### Community 200 - "InventoryItems"
Cohesion: 0.16
Nodes (5): {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator

### Community 206 - "Illuminate\Support\Collection"
Cohesion: 0.15
Nodes (12): {closure#10}(), {closure#12}(), {closure#25}(), {closure#6}(), {closure#8}(), {closure#4}(), {closure#6}(), {closure#12}() (+4 more)

### Community 218 - "DonationReceivingTest.php"
Cohesion: 0.12
Nodes (10): {closure#10}(), {closure#11}(), {closure#12}(), {closure#19}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}() (+2 more)

### Community 228 - "Receiving.php"
Cohesion: 0.07
Nodes (3): {closure#10}(), {closure#2}(), Receiving

### Community 236 - "App\Models\StorageLocations"
Cohesion: 0.06
Nodes (8): StorageLocationForm, InventoryBatchesForm, InventoryCategoriesForm, {closure#2}(), InventoryTransactionsForm, StorageLocationsForm, CategoryCreateForm, BatchCodeGenerator

### Community 238 - "StockMonitoringTest.php"
Cohesion: 0.29
Nodes (16): {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+8 more)

### Community 239 - "PasswordValidationRules"
Cohesion: 0.13
Nodes (6): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, Logout, DeleteUserForm

### Community 242 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.25
Nodes (6): clearBatch, clearFilters, clearReceipt, exportPdf, showExceptions, toggleSortDirection

### Community 252 - "MailTemplatesTest.php"
Cohesion: 0.20
Nodes (9): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#9}() (+1 more)

### Community 260 - "App\Models\Suppliers"
Cohesion: 0.15
Nodes (5): ReceiptStatus, Closed, Complete, Partial, SuppliersFactory

### Community 266 - "StockStatus"
Cohesion: 0.13
Nodes (8): StockStatus, Critical, Healthy, Out, Reorder, {closure#2}(), {closure#4}(), {closure#5}()

### Community 268 - "App\Livewire\Forms\ApprovalsForm"
Cohesion: 0.23
Nodes (6): ApprovalDecision, Approved, Rejected, {closure#1}(), {closure#2}(), {closure#3}()

### Community 269 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 271 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

## Knowledge Gaps
- **106 isolated node(s):** `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+101 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 895 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **200 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `InventoryItems` connect `InventoryItems` to `Illuminate\Database\Eloquent\Factories\Factory`, `User`, `Illuminate\Database\Seeder`, `Livewire\Component`, `InventoryBatchesFactory.php`, `RequisitionStatusUpdated`, `StockStatus`, `App\Livewire\Forms\ApprovalsForm`, `App\Models\StorageLocations`, `Illuminate\Database\Eloquent\Builder`, `App\Models\InventoryItems`, `StockMonitoring`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **What connects `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form` to the rest of the system?**
  _106 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Livewire\WithPagination` be split into smaller, more focused modules?**
  _Cohesion score 0.08108108108108109 - nodes in this community are weakly interconnected._
- **Why does `ExpirationMonitoring` connect `ExpirationMonitoring` to `Livewire\WithPagination`, `Livewire\Component`, `App\Livewire\Inventory\ReceivingCreateForm`, `ExpirationMonitoringTest.php`, `Illuminate\Support\Collection`, `Illuminate\Database\Eloquent\Builder`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `User` connect `User` to `PasswordResetTest.php`, `Illuminate\Database\Seeder`, `Requisitions`, `Livewire\Component`, `StockStatus`, `PasswordValidationRules`, `EmailVerificationTest.php`, `ProfileUpdateTest.php`, `Illuminate\Support\Str`, `SecurityTest.php`?**
  _High betweenness centrality (0.020) - this node is a cross-community bridge._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.13157894736842105 - nodes in this community are weakly interconnected._