# Graph Report - inventory-system-prototype  (2026-11-26)

## Corpus Check
- 327 files · ~99,939 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 18, .css 2, .example 1)

## Summary
- 2288 nodes · 4538 edges · 274 communities (75 shown, 199 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 113 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `090b9ae7`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ApprovalsTest.php
- ExpirationMonitoring.php
- Frontend NPM Dependencies
- Illuminate\Database\Seeder
- AdjustmentType
- static
- StockStatusMonitor
- InventoryBatchesFactory.php
- StockReconciliation
- StockStatusAlert
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
- App\Services\Inventory\StockMetrics
- Illuminate\Database\Eloquent\Model
- ExpirationMonitoring
- StockReconciliation
- InventoryAdjustmentsForm
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
- StockMonitoringTest.php
- App\Models\InventoryBatches
- Security Blade View
- HeaderTest.php
- ReceivingTest.php
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
- ApprovalsForm
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
- item
- transaction-history.blade
- profile.blade
- bootstrap.blade
- simple-bootstrap.blade
- simple-tailwind.blade
- tailwind.blade
- IssuancesTest.php
- Database\Factories\InventoryItemsFactory
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
- App\Models\InventoryAdjustments
- public-portal.blade.php
- FortifyServiceProvider
- guest.blade.php
- App\Enums\RequisitionStatus
- CategoryMultiSelect
- DonationReceivingTest.php
- Carbon\CarbonInterface
- Receiving.php
- InventoryBatches
- Pest.php
- App\Models\StorageLocations
- Security
- App\Models\InventoryCategories
- monitoring/stock-reconciliation.blade.php
- MailTemplatesTest.php
- 2026_11_16_071730_create_suppliers_table.php
- ReceiptStatus
- .request
- StockStatus
- Approvals
- App\Livewire\Forms\ApprovalsForm
- ExpirationMonitoringTest.php
- RequestEmailNotificationsTest.php
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
9. `Issuances` - 25 edges
10. `Reports` - 25 edges

## Surprising Connections (you probably didn't know these)
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `ledger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/ReportsTest.php → app/Models/InventoryTransactions.php
- `stockLedger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/StockMonitoringTest.php → app/Models/InventoryTransactions.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#11}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (274 total, 199 thin omitted)

### Community 0 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 1 - "ExpirationMonitoring.php"
Cohesion: 0.12
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), Profile

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "Illuminate\Database\Seeder"
Cohesion: 0.07
Nodes (9): SuppliersForm, SupplierForm, Suppliers, DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder (+1 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.11
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, adjust(), {closure#12}() (+8 more)

### Community 5 - "static"
Cohesion: 0.17
Nodes (3): InventoryAdjustmentsFactory, StockReceiptsFactory, UserFactory

### Community 6 - "StockStatusMonitor"
Cohesion: 0.24
Nodes (3): CheckStockStatus, {closure#2}(), StockStatusMonitor

### Community 8 - "InventoryBatchesFactory.php"
Cohesion: 0.14
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory

### Community 9 - "StockReconciliation"
Cohesion: 0.09
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#8}(), {closure#9}() (+1 more)

### Community 11 - "Illuminate\Support\Collection"
Cohesion: 0.16
Nodes (8): {closure#10}(), {closure#12}(), {closure#25}(), {closure#6}(), {closure#8}(), AdministrativeAreaService, {closure#4}(), {closure#6}()

### Community 12 - "InventoryItemsForm"
Cohesion: 0.18
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 15 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.05
Nodes (38): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+30 more)

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
Nodes (34): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+26 more)

### Community 22 - "App\Models\InventoryItems"
Cohesion: 0.18
Nodes (14): {closure#11}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+6 more)

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.09
Nodes (39): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+31 more)

### Community 26 - "RequisitionStatus"
Cohesion: 0.17
Nodes (19): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, assertStatusMailSent() (+11 more)

### Community 27 - "App\Services\Inventory\StockMetrics"
Cohesion: 0.09
Nodes (11): {closure#2}(), {closure#2}(), {closure#7}(), {closure#9}(), {closure#26}(), {closure#1}(), {closure#2}(), {closure#3}() (+3 more)

### Community 28 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.23
Nodes (3): Attachments, AuditLogs, StockReceiptLines

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 37 - "DashboardTest.php"
Cohesion: 0.10
Nodes (35): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#8}(), {closure#4}(), {closure#10}(), {closure#11}() (+27 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Requisitions.php"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#1}(), {closure#2}()

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 46 - "App\Models\StockReceipts"
Cohesion: 0.18
Nodes (3): {closure#10}(), {closure#4}(), DeliveryProgress

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 48 - "StockMonitoringTest.php"
Cohesion: 0.29
Nodes (16): {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+8 more)

### Community 49 - "App\Models\InventoryBatches"
Cohesion: 0.12
Nodes (14): {closure#22}(), {closure#23}(), {closure#1}(), {closure#1}(), {closure#2}(), {closure#4}(), {closure#1}(), {closure#2}() (+6 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "HeaderTest.php"
Cohesion: 0.09
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 52 - "ReceivingTest.php"
Cohesion: 0.21
Nodes (5): {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), receivingForm()

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "StockStatusAlertsTest.php"
Cohesion: 0.24
Nodes (14): alertBaseline(), alertBatch(), alertIssue(), alertItem(), alertReceive(), alertsSentTo(), {closure#11}(), {closure#12}() (+6 more)

### Community 55 - "ReportsTest.php"
Cohesion: 0.27
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

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

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

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

### Community 124 - "App\Models\InventoryAdjustments"
Cohesion: 0.16
Nodes (5): StockIssuances, {closure#1}(), {closure#2}(), {closure#3}(), TransactionReferences

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.20
Nodes (7): configurations.storage-location-form, delete, editViewing, inventory.supplier-form, partials.configuration-heading, sort, toggleActive

### Community 127 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.15
Nodes (4): {closure#7}(), InventoryTransactions, RequisitionItems, StockIssuanceBatches

### Community 129 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.16
Nodes (3): InventoryCategoriesFactory, StorageLocationsFactory, SuppliersFactory

### Community 200 - "InventoryItems"
Cohesion: 0.15
Nodes (7): {closure#1}(), {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator, {closure#1}()

### Community 218 - "DonationReceivingTest.php"
Cohesion: 0.12
Nodes (10): {closure#10}(), {closure#11}(), {closure#12}(), {closure#19}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}() (+2 more)

### Community 236 - "App\Models\StorageLocations"
Cohesion: 0.07
Nodes (7): StorageLocationForm, InventoryBatchesForm, InventoryCategoriesForm, {closure#2}(), InventoryTransactionsForm, StorageLocationsForm, CategoryCreateForm

### Community 239 - "Security"
Cohesion: 0.06
Nodes (8): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, Logout, DeleteUserForm, Security, RecoveryCodes

### Community 242 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.25
Nodes (6): clearBatch, clearFilters, clearReceipt, exportPdf, showExceptions, toggleSortDirection

### Community 252 - "MailTemplatesTest.php"
Cohesion: 0.14
Nodes (12): RequisitionReceived, RequisitionStatusUpdated, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+4 more)

### Community 260 - "ReceiptStatus"
Cohesion: 0.33
Nodes (4): ReceiptStatus, Closed, Complete, Partial

### Community 266 - "StockStatus"
Cohesion: 0.14
Nodes (7): StockStatus, Critical, Healthy, Out, Reorder, {closure#4}(), {closure#5}()

### Community 268 - "App\Livewire\Forms\ApprovalsForm"
Cohesion: 0.21
Nodes (6): ApprovalDecision, Approved, Rejected, {closure#2}(), {closure#3}(), {closure#1}()

### Community 269 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 271 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

## Knowledge Gaps
- **106 isolated node(s):** `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+101 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 898 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **199 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Laravel\Fortify\Features`, `ExpirationMonitoring.php`, `Illuminate\Database\Eloquent\Factories\Factory`, `Illuminate\Database\Seeder`, `PasswordResetTest.php`, `EmailVerificationTest.php`, `Requisitions`, `StockStatus`, `Security`, `App\Models\InventoryCategories`, `ProfileUpdateTest.php`, `SecurityTest.php`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **What connects `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form` to the rest of the system?**
  _106 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `ExpirationMonitoring.php` be split into smaller, more focused modules?**
  _Cohesion score 0.125 - nodes in this community are weakly interconnected._
- **Why does `StockReconciliation` connect `StockReconciliation` to `StockReconciliationTest.php`, `StockReconciliation`, `Receiving.php`, `Livewire\Component`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `Illuminate\Database\Seeder`, `InventoryBatchesFactory.php`, `StockStatus`, `InventoryItemsForm`, `App\Livewire\Forms\ApprovalsForm`, `App\Models\StorageLocations`, `Illuminate\Database\Eloquent\Relations\HasMany`, `App\Models\InventoryCategories`, `Illuminate\Database\Eloquent\Builder`, `MailTemplatesTest.php`, `App\Models\InventoryItems`, `StockMonitoring`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.022) - this node is a cross-community bridge._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.06951219512195123 - nodes in this community are weakly interconnected._