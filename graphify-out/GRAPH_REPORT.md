# Graph Report - inventory-system-prototype  (2026-11-26)

## Corpus Check
- 330 files · ~100,868 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 18, .css 2, .example 1)

## Summary
- 2319 nodes · 4556 edges · 265 communities (78 shown, 187 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 77 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `fc98101e`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ApprovalsTest.php
- Security
- Frontend NPM Dependencies
- Illuminate\Database\Seeder
- AdjustmentType
- ExpirationMonitoring
- StockStatusMonitor
- ReceivingCreateForm
- Illuminate\Database\Eloquent\Factories\Factory
- StockReconciliation
- RequisitionStatusUpdated
- AdministrativeAreaService
- InventoryItemsForm
- Reports Aggregation
- Illuminate\Database\Eloquent\Builder
- PartialDeliveryTest.php
- Illuminate\Database\Schema\Blueprint
- StockReconciliationTest.php
- Livewire\Component
- Inventory/StockReconciliation.php
- InventoryItems
- Stocks
- DeliveryTimelineTest.php
- StockMonitoring
- App\Services\Inventory\StockMetrics
- InventoryBatches
- Attachments.php
- InventoryAdjustments
- SecurityTest.php
- Profile
- StockReceiptsForm
- User
- chart.js
- AdministrativeAreaService.php
- DashboardTest.php
- Requisitions
- Dashboard
- Composer Scripts
- Requisitions.php
- ExpirationMonitoringTest.php
- StockReconciliation
- Composer Metadata
- Composer Dev Dependencies
- App\Models\StockReceipts
- Deploy Workflow
- Issuances.php
- App\Services\Inventory\DeliveryProgress
- Security Blade View
- HeaderTest.php
- App\Models\StorageLocations
- RequestsTest.php
- StockStatusAlertsTest.php
- ReportsTest.php
- TransactionHistoryTest.php
- receiving.blade.php
- Receiving.php
- Illuminate\Support\Collection
- Composer Runtime Deps
- FortifyServiceProvider.php
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Items
- requests.blade.php
- RequisitionsForm
- TelescopeServiceProvider.php
- ExpirationMonitoring.php
- Composer Config
- receipt-details.blade.php
- approvals.blade
- composer
- composer (2)
- table
- Suppliers
- stock-monitoring.blade
- InventoryItems.php
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
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- App\Models\Approvals
- InventoryAdjustmentsFactory
- 2026_11_26_143822_create_telescope_entries_table.php
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- sidebar.blade.php
- card.blade
- simple.blade
- split.blade
- App\Models\InventoryAdjustments
- request-form.blade.php
- inventory/suppliers.blade.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- categories.blade
- appearance.blade
- StorageLocations
- RecoveryCodes
- Illuminate\Contracts\View\View
- StorageLocations
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- Pest.php
- public-portal.blade.php
- configurations/storage-locations.blade.php
- guest.blade.php
- .apply
- CategoryMultiSelect
- DonationReceivingTest.php
- inventory/items.blade.php
- monitoring/stock-reconciliation.blade.php
- receiving-create-form.blade.php
- Receiving
- placeholders/storage-locations.blade.php
- PasswordValidationRules
- inventory/stocks.blade.php
- MailTemplatesTest.php
- 2026_11_16_071730_create_suppliers_table.php
- 0001_01_01_000002_create_jobs_table.php
- StockStatus
- Approvals
- RequestEmailNotificationsTest.php
- TrackRequestTest.php
- 2026_11_16_081104_create_inventory_categories_table.php
- App\Support\MailLogo
- web.php

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 108 edges
2. `InventoryBatches` - 78 edges
3. `StockReceipts` - 70 edges
4. `User` - 63 edges
5. `InventoryTransactionType` - 55 edges
6. `Requisitions` - 55 edges
7. `RequisitionStatus` - 53 edges
8. `AdjustmentType` - 51 edges
9. `InventoryTransactions` - 46 edges
10. `dtRoot()` - 37 edges

## Surprising Connections (you probably didn't know these)
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [EXTRACTED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#6}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php

## Import Cycles
- None detected.

## Communities (265 total, 187 thin omitted)

### Community 0 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.07
Nodes (35): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+27 more)

### Community 3 - "Illuminate\Database\Seeder"
Cohesion: 0.15
Nodes (5): InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 4 - "AdjustmentType"
Cohesion: 0.11
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, adjust(), {closure#12}() (+8 more)

### Community 6 - "StockStatusMonitor"
Cohesion: 0.15
Nodes (5): CheckStockStatus, {closure#2}(), {closure#4}(), {closure#5}(), StockStatusMonitor

### Community 8 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.06
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, StockReceiptsFactory, StorageLocationsFactory, SuppliersFactory (+1 more)

### Community 9 - "StockReconciliation"
Cohesion: 0.05
Nodes (4): StockReconciliation, {closure#8}(), {closure#9}(), TransactionHistory

### Community 10 - "RequisitionStatusUpdated"
Cohesion: 0.23
Nodes (4): RequisitionStatusUpdated, {closure#1}(), StockStatusAlert, {closure#15}()

### Community 12 - "InventoryItemsForm"
Cohesion: 0.17
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 15 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.10
Nodes (24): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+16 more)

### Community 17 - "PartialDeliveryTest.php"
Cohesion: 0.09
Nodes (40): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+32 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.16
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+1 more)

### Community 19 - "StockReconciliationTest.php"
Cohesion: 0.15
Nodes (34): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+26 more)

### Community 21 - "Inventory/StockReconciliation.php"
Cohesion: 0.11
Nodes (9): {closure#10}(), {closure#14}(), {closure#16}(), {closure#18}(), {closure#20}(), {closure#21}(), {closure#4}(), {closure#5}() (+1 more)

### Community 22 - "InventoryItems"
Cohesion: 0.11
Nodes (18): {closure#2}(), {closure#11}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}() (+10 more)

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.09
Nodes (39): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+31 more)

### Community 26 - "App\Services\Inventory\StockMetrics"
Cohesion: 0.12
Nodes (9): {closure#2}(), {closure#7}(), InventoryTransactions, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+1 more)

### Community 27 - "InventoryBatches"
Cohesion: 0.16
Nodes (10): {closure#22}(), {closure#23}(), InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}() (+2 more)

### Community 29 - "InventoryAdjustments"
Cohesion: 0.12
Nodes (5): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}(), InventoryAdjustments

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "AdministrativeAreaService.php"
Cohesion: 0.15
Nodes (7): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#9}()

### Community 37 - "DashboardTest.php"
Cohesion: 0.05
Nodes (61): InventoryTransactionType, Adjustment, Issuance, Receipt, RequisitionStatus, Approved, Cancelled, Issued (+53 more)

### Community 38 - "Requisitions"
Cohesion: 0.11
Nodes (9): ApprovalDecision, Approved, Rejected, {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#1}() (+1 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 42 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 43 - "StockReconciliation"
Cohesion: 0.27
Nodes (4): {closure#1}(), {closure#22}(), {closure#8}(), StockReconciliation

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 46 - "App\Models\StockReceipts"
Cohesion: 0.22
Nodes (4): {closure#10}(), {closure#4}(), StockReceipts, DeliveryProgress

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 48 - "Issuances.php"
Cohesion: 0.15
Nodes (5): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}()

### Community 49 - "App\Services\Inventory\DeliveryProgress"
Cohesion: 0.12
Nodes (4): ReceiptStatus, Closed, Complete, Partial

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "HeaderTest.php"
Cohesion: 0.17
Nodes (5): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}(), {closure#4}()

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
Cohesion: 0.31
Nodes (6): {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

### Community 57 - "receiving.blade.php"
Cohesion: 0.33
Nodes (5): closeDelivery, confirmClose, livewire.inventory.partials.receipt-details, receiveRemaining, view

### Community 59 - "Illuminate\Support\Collection"
Cohesion: 0.15
Nodes (7): {closure#10}(), {closure#12}(), {closure#25}(), {closure#6}(), {closure#8}(), {closure#1}(), {closure#12}()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.18
Nodes (11): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/telescope, laravel/tinker, livewire/blaze (+3 more)

### Community 61 - "FortifyServiceProvider.php"
Cohesion: 0.07
Nodes (9): AppServiceProvider, {closure#1}(), {closure#10}(), {closure#8}(), {closure#9}(), FortifyServiceProvider, {closure#1}(), {closure#2}() (+1 more)

### Community 65 - "requests.blade.php"
Cohesion: 0.50
Nodes (3): cancel, livewire.requisitions.partials.requester-details, view

### Community 68 - "TelescopeServiceProvider.php"
Cohesion: 0.24
Nodes (3): {closure#1}(), {closure#2}(), TelescopeServiceProvider

### Community 69 - "ExpirationMonitoring.php"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

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

### Community 80 - "Suppliers"
Cohesion: 0.06
Nodes (7): InventoryBatchesForm, InventoryCategoriesForm, InventoryTransactionsForm, SuppliersForm, CategoryCreateForm, SupplierForm, Suppliers

### Community 82 - "stock-monitoring.blade"
Cohesion: 0.40
Nodes (4): livewire.monitoring.partials.stock-detail, clearFilters, filterStatus, toggle

### Community 83 - "InventoryItems.php"
Cohesion: 0.20
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), InventoryItemsFactory

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
Nodes (3): resendVerificationNotification, partials.settings-heading, settings.delete-user-form

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
Cohesion: 0.10
Nodes (18): IssuancesForm, Issuances, approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}() (+10 more)

### Community 109 - "issuances.blade"
Cohesion: 0.40
Nodes (4): confirmRelease, open, release, livewire.requisitions.partials.requester-details

### Community 114 - "InventoryAdjustmentsFactory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

### Community 115 - "2026_11_26_143822_create_telescope_entries_table.php"
Cohesion: 0.38
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), down(), getConnection(), up()

### Community 124 - "App\Models\InventoryAdjustments"
Cohesion: 0.18
Nodes (6): StockIssuances, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), TransactionReferences

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): edit, addItem, removeItem, startOver, submit, track

### Community 126 - "inventory/suppliers.blade.php"
Cohesion: 0.40
Nodes (4): inventory.supplier-form, delete, sort, toggleActive

### Community 127 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.08
Nodes (7): AuditLogs, {closure#1}(), {closure#1}(), RequisitionItems, StockIssuanceBatches, StockIssuanceItems, StockReceiptLines

### Community 208 - "configurations/storage-locations.blade.php"
Cohesion: 0.40
Nodes (4): configurations.storage-location-form, delete, partials.configuration-heading, sort

### Community 218 - "DonationReceivingTest.php"
Cohesion: 0.07
Nodes (31): {closure#10}(), {closure#11}(), {closure#12}(), {closure#19}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}() (+23 more)

### Community 219 - "inventory/items.blade.php"
Cohesion: 0.50
Nodes (3): editViewing, sort, view

### Community 223 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.50
Nodes (3): exportPdf, clearFilters, showExceptions

### Community 239 - "PasswordValidationRules"
Cohesion: 0.15
Nodes (5): CreateNewUser, ResetUserPassword, PasswordValidationRules, Logout, DeleteUserForm

### Community 242 - "inventory/stocks.blade.php"
Cohesion: 0.40
Nodes (4): clearBatch, clearReceipt, clearFilters, toggleSortDirection

### Community 252 - "MailTemplatesTest.php"
Cohesion: 0.18
Nodes (10): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}() (+2 more)

### Community 260 - "0001_01_01_000002_create_jobs_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 266 - "StockStatus"
Cohesion: 0.16
Nodes (5): StockStatus, Critical, Healthy, Out, Reorder

### Community 271 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

## Knowledge Gaps
- **120 isolated node(s):** `Damage`, `Loss`, `ExpiryWriteOff`, `CountCorrection`, `Return` (+115 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 930 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **187 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `ApprovalsTest.php`, `PasswordResetTest.php`, `Illuminate\Database\Seeder`, `AdjustmentType`, `StockStatusMonitor`, `Illuminate\Database\Eloquent\Factories\Factory`, `StockReconciliation`, `Illuminate\Database\Eloquent\Builder`, `TrackRequestTest.php`, `PartialDeliveryTest.php`, `StockReconciliationTest.php`, `DeliveryTimelineTest.php`, `InventoryBatches`, `SecurityTest.php`, `Profile`, `Laravel\Fortify\Features`, `DashboardTest.php`, `Requisitions`, `ExpirationMonitoringTest.php`, `HeaderTest.php`, `RequestsTest.php`, `StockStatusAlertsTest.php`, `ReportsTest.php`, `TransactionHistoryTest.php`, `TelescopeServiceProvider.php`, `StorageLocations`, `Suppliers`, `.apply`, `DonationReceivingTest.php`, `IssuancesTest.php`, `EmailVerificationTest.php`, `PasswordValidationRules`, `ProfileUpdateTest.php`, `InventoryAdjustmentsFactory`?**
  _High betweenness centrality (0.104) - this node is a cross-community bridge._
- **What connects `Damage`, `Loss`, `ExpiryWriteOff` to the rest of the system?**
  _120 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Security` be split into smaller, more focused modules?**
  _Cohesion score 0.14761904761904762 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `ApprovalsTest.php`, `Illuminate\Database\Seeder`, `AdjustmentType`, `StockStatusMonitor`, `ReceivingCreateForm`, `Illuminate\Database\Eloquent\Factories\Factory`, `RequisitionStatusUpdated`, `InventoryItemsForm`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Database\Eloquent\Builder`, `RequestEmailNotificationsTest.php`, `PartialDeliveryTest.php`, `TrackRequestTest.php`, `StockReconciliationTest.php`, `Inventory/StockReconciliation.php`, `DeliveryTimelineTest.php`, `App\Services\Inventory\StockMetrics`, `DashboardTest.php`, `Requisitions`, `ExpirationMonitoringTest.php`, `Issuances.php`, `App\Services\Inventory\DeliveryProgress`, `RequestsTest.php`, `StockStatusAlertsTest.php`, `ReportsTest.php`, `TransactionHistoryTest.php`, `Receiving.php`, `Items`, `ExpirationMonitoring.php`, `Livewire\WithPagination`, `StorageLocations`, `InventoryItems.php`, `BatchCodeGenerator`, `DonationReceivingTest.php`, `IssuancesTest.php`, `MailTemplatesTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`?**
  _High betweenness centrality (0.060) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07254623044096728 - nodes in this community are weakly interconnected._
- **Why does `StockReceipts` connect `App\Models\StockReceipts` to `ApprovalsTest.php`, `Illuminate\Database\Seeder`, `AdjustmentType`, `ReceivingCreateForm`, `Illuminate\Database\Eloquent\Factories\Factory`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Database\Eloquent\Builder`, `PartialDeliveryTest.php`, `StockReconciliationTest.php`, `InventoryItems`, `Stocks`, `DeliveryTimelineTest.php`, `StockReceiptsForm`, `DashboardTest.php`, `ExpirationMonitoringTest.php`, `App\Services\Inventory\DeliveryProgress`, `RequestsTest.php`, `StockStatusAlertsTest.php`, `ReportsTest.php`, `TransactionHistoryTest.php`, `Receiving.php`, `StorageLocations`, `StockReceipts.php`, `Suppliers`, `BatchCodeGenerator`, `DonationReceivingTest.php`, `Receiving`, `IssuancesTest.php`, `App\Models\InventoryAdjustments`, `Illuminate\Database\Eloquent\Relations\BelongsTo`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.10574712643678161 - nodes in this community are weakly interconnected._