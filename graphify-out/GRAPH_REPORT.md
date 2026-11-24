# Graph Report - inventory-system-prototype  (2026-11-24)

## Corpus Check
- 287 files · ~72,093 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 2, .example 1)

## Summary
- 1822 nodes · 3368 edges · 228 communities (60 shown, 168 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 89 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `82a64677`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- App\Livewire\Monitoring\StockMonitoring
- PasswordValidationRules
- Frontend NPM Dependencies
- DashboardTest.php
- AdjustmentType
- Livewire\WithPagination
- Issuances
- InventoryAdjustmentsForm
- bootstrap/app.php
- Suppliers
- StockStatusAlertsTest.php
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- App\Models\InventoryTransactions
- Security
- Livewire\Form
- Illuminate\Database\Seeder
- Illuminate\Database\Schema\Blueprint
- StockMonitoringTest.php
- App\Livewire\Forms\ApprovalsForm
- App\Livewire\Monitoring\Reports
- Stocks Browser
- ExpirationMonitoringTest.php
- ApprovalsTest.php
- App\Livewire\Monitoring\StockMonitoring
- Illuminate\Support\Carbon
- ReportsTest.php
- InventoryItemsForm
- App\Livewire\Inventory\Adjustments
- Illuminate\Database\Eloquent\Builder
- App\Models\StorageLocations
- InventoryAdjustmentsFactory
- User
- chart.js
- StockStatus
- Dashboard
- Composer Scripts
- Profile
- HeaderTest.php
- Composer Metadata
- Composer Dev Dependencies
- TransactionHistoryTest.php
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- InventoryBatches
- InventoryItems
- RequestsTest.php
- InventoryTransactionType
- StockReceiptsFactory.php
- Requisitions
- App\Models\StockIssuances
- App\Livewire\Requisitions\Requests
- Composer Runtime Deps
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- InventoryCategoriesForm
- Suppliers
- Illuminate\Database\Eloquent\Factories\Factory
- Composer Config
- InventoryAdjustments
- RequisitionsForm
- approvals.blade
- composer
- composer (2)
- ReceivingTest.php
- table
- table (2)
- Illuminate\Database\Eloquent\Model
- stock-monitoring.blade
- UserFactory.php
- expiration-monitoring.blade
- StockMonitoring
- table (4)
- RecoveryCodes
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
- ReceivingCreateForm
- requests.blade.php
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- App\Livewire\Forms\RequisitionsForm
- stocks.blade
- Approvals
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- partials.head
- card.blade
- simple.blade
- split.blade
- StorageLocations
- request-form.blade.php
- storage-locations.blade.php
- Suppliers
- categories.blade
- items.blade
- appearance.blade
- App\Services\AdministrativeAreas\AdministrativeAreaService
- Logout.php
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- ApprovalsForm
- public-portal.blade.php
- .apply
- guest.blade.php
- Livewire\Component
- Attachments.php
- InventoryTransactions
- Pest.php

## God Nodes (most connected - your core abstractions)
1. `InventoryBatches` - 50 edges
2. `InventoryItems` - 48 edges
3. `AdjustmentType` - 44 edges
4. `User` - 37 edges
5. `StockReceipts` - 30 edges
6. `InventoryTransactionType` - 29 edges
7. `RequisitionStatus` - 28 edges
8. `Requisitions` - 28 edges
9. `Reports` - 23 edges
10. `InventoryTransactions` - 21 edges

## Surprising Connections (you probably didn't know these)
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (228 total, 168 thin omitted)

### Community 0 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.15
Nodes (12): {closure#11}(), {closure#13}(), {closure#14}(), {closure#7}(), {closure#9}(), {closure#3}(), {closure#4}(), {closure#5}() (+4 more)

### Community 1 - "PasswordValidationRules"
Cohesion: 0.17
Nodes (5): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, DeleteUserForm

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "DashboardTest.php"
Cohesion: 0.06
Nodes (48): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#8}() (+40 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.13
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#6}(), adjust() (+8 more)

### Community 5 - "Livewire\WithPagination"
Cohesion: 0.08
Nodes (3): Categories, Items, Receiving

### Community 7 - "InventoryAdjustmentsForm"
Cohesion: 0.17
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 8 - "bootstrap/app.php"
Cohesion: 0.14
Nodes (13): {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+5 more)

### Community 10 - "StockStatusAlertsTest.php"
Cohesion: 0.06
Nodes (30): RequisitionReceived, RequisitionStatusUpdated, {closure#1}(), StockStatusAlert, MailLogo, alertBaseline(), alertBatch(), alertIssue() (+22 more)

### Community 12 - "Transaction History Filters"
Cohesion: 0.15
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 14 - "App\Models\InventoryTransactions"
Cohesion: 0.21
Nodes (5): {closure#2}(), {closure#26}(), {closure#7}(), {closure#1}(), {closure#1}()

### Community 17 - "Illuminate\Database\Seeder"
Cohesion: 0.21
Nodes (4): InventoryCategoriesSeeder, InventoryItemsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 20 - "App\Livewire\Forms\ApprovalsForm"
Cohesion: 0.24
Nodes (6): ApprovalDecision, Approved, Rejected, {closure#1}(), {closure#2}(), {closure#3}()

### Community 24 - "ExpirationMonitoringTest.php"
Cohesion: 0.12
Nodes (12): {closure#5}(), ExpirationMonitoring, {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+4 more)

### Community 25 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 26 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.12
Nodes (7): {closure#10}(), {closure#12}(), {closure#22}(), {closure#23}(), {closure#25}(), {closure#6}(), {closure#8}()

### Community 28 - "ReportsTest.php"
Cohesion: 0.26
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 29 - "InventoryItemsForm"
Cohesion: 0.13
Nodes (4): InventoryItemsForm, ItemCreateForm, ItemEditForm, InventoryCategories

### Community 31 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#1}(), {closure#2}(), {closure#3}() (+8 more)

### Community 33 - "InventoryAdjustmentsFactory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "StockStatus"
Cohesion: 0.06
Nodes (14): CheckStockStatus, StockStatus, Critical, Healthy, Out, Reorder, {closure#5}(), AppServiceProvider (+6 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 43 - "HeaderTest.php"
Cohesion: 0.15
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 46 - "TransactionHistoryTest.php"
Cohesion: 0.24
Nodes (7): StorageLocations, {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "InventoryBatches"
Cohesion: 0.25
Nodes (8): InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 52 - "InventoryItems"
Cohesion: 0.13
Nodes (7): {closure#1}(), {closure#2}(), InventoryItems, BatchAllocator, {closure#1}(), {closure#1}(), {closure#2}()

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "InventoryTransactionType"
Cohesion: 0.18
Nodes (7): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#1}(), historyEntry()

### Community 55 - "StockReceiptsFactory.php"
Cohesion: 0.20
Nodes (4): ReceiptStatus, Complete, Partial, StockReceiptsFactory

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 69 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.05
Nodes (23): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, InventoryItemsFactory, StorageLocationsFactory, SuppliersFactory (+15 more)

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 72 - "InventoryAdjustments"
Cohesion: 0.15
Nodes (6): InventoryAdjustments, StockReceipts, {closure#1}(), {closure#2}(), {closure#4}(), TransactionReferences

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 78 - "ReceivingTest.php"
Cohesion: 0.39
Nodes (4): {closure#3}(), {closure#4}(), {closure#5}(), receivingForm()

### Community 81 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (5): Approvals, AuditLogs, RequisitionItems, StockIssuanceBatches, StockIssuanceItems

### Community 82 - "stock-monitoring.blade"
Cohesion: 0.40
Nodes (4): livewire.monitoring.partials.stock-detail, clearFilters, filterStatus, toggle

### Community 84 - "expiration-monitoring.blade"
Cohesion: 0.50
Nodes (3): confirmWriteOff, clearFilters, filterStatus

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

### Community 106 - "ReceivingCreateForm"
Cohesion: 0.14
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 108 - "requests.blade.php"
Cohesion: 0.50
Nodes (3): cancel, livewire.requisitions.partials.requester-details, view

### Community 109 - "issuances.blade"
Cohesion: 0.50
Nodes (3): open, release, livewire.requisitions.partials.requester-details

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.29
Nodes (6): configurations.storage-location-form, delete, inventory.supplier-form, partials.configuration-heading, sort, toggleActive

### Community 198 - "App\Services\AdministrativeAreas\AdministrativeAreaService"
Cohesion: 0.18
Nodes (6): {closure#11}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 211 - "Livewire\Component"
Cohesion: 0.12
Nodes (3): AuditLogs, PublicPortal, Appearance

## Knowledge Gaps
- **96 isolated node(s):** `toggleSortDirection`, `clearFilters`, `export`, `toggleOrder`, `clearFilters` (+91 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 752 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **168 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `PasswordValidationRules`, `StockMonitoringTest.php`, `ExpirationMonitoringTest.php`, `ReportsTest.php`, `InventoryAdjustmentsFactory`, `StockStatus`, `EmailVerificationTest.php`, `Profile`, `SecurityTest.php`, `TransactionHistoryTest.php`, `PasswordResetTest.php`, `InventoryBatches`, `InventoryTransactionType`, `Requisitions`, `ProfileUpdateTest.php`, `InventoryAdjustments`, `AuthenticationTest.php`, `.apply`, `UserFactory.php`, `Suppliers`?**
  _High betweenness centrality (0.062) - this node is a cross-community bridge._
- **What connects `toggleSortDirection`, `clearFilters`, `export` to the rest of the system?**
  _96 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `App\Livewire\Monitoring\StockMonitoring`, `StockStatus`, `Livewire\WithPagination`, `Illuminate\Database\Eloquent\Factories\Factory`, `InventoryAdjustments`, `StockStatusAlertsTest.php`, `TransactionHistoryTest.php`, `Livewire\Form`, `Illuminate\Database\Eloquent\Model`, `Illuminate\Database\Seeder`, `StockMonitoringTest.php`, `App\Livewire\Forms\ApprovalsForm`, `StockMonitoring`, `ExpirationMonitoringTest.php`, `Illuminate\Support\Carbon`, `ReportsTest.php`, `InventoryItemsForm`, `Illuminate\Database\Eloquent\Builder`?**
  _High betweenness centrality (0.059) - this node is a cross-community bridge._
- **Should `DashboardTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.05859969558599695 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `InventoryBatches` to `DashboardTest.php`, `InventoryAdjustmentsForm`, `App\Models\InventoryTransactions`, `Livewire\Form`, `StockMonitoringTest.php`, `App\Livewire\Monitoring\Reports`, `Stocks Browser`, `ExpirationMonitoringTest.php`, `App\Livewire\Monitoring\StockMonitoring`, `Illuminate\Support\Carbon`, `ReportsTest.php`, `Illuminate\Database\Eloquent\Builder`, `InventoryAdjustmentsFactory`, `TransactionHistoryTest.php`, `InventoryItems`, `App\Livewire\Requisitions\Issuances`, `Illuminate\Database\Eloquent\Factories\Factory`, `InventoryAdjustments`, `.apply`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.12923076923076923 - nodes in this community are weakly interconnected._