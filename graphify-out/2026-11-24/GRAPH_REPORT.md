# Graph Report - inventory-system-prototype  (2026-11-24)

## Corpus Check
- 272 files · ~64,926 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 17, .example 1, .neon 1)

## Summary
- 1665 nodes · 3016 edges · 232 communities (62 shown, 170 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 73 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `645d8654`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- App\Livewire\Monitoring\StockMonitoring
- BatchAllocator
- Frontend NPM Dependencies
- DashboardTest.php
- AdjustmentType
- Livewire\WithPagination
- Issuances
- InventoryBatches
- bootstrap/app.php
- ProfileValidationRules
- Item Create/Edit Forms
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- ReceivingCreateForm
- Security Settings Component
- Attachments.php
- Issuances Tests
- Illuminate\Database\Schema\Blueprint
- Database Seeders
- DashboardTest.php
- MailViewsTest.php
- Stocks Browser
- Inventory Adjustment Form
- Expiration Monitoring
- Service Providers
- ReceiptStatus
- Fortify User Actions
- Stock Monitoring Dashboard
- User Model & Auth
- InventoryBatches
- Requisitions
- App\Models\Requisitions
- JS Charts
- Stock Status Enum
- ReportsTest.php
- Reports.php
- Dashboard
- Composer Scripts
- Livewire\Form
- Adjustments List
- Breadcrumbs Header
- Composer Metadata
- Composer Dev Dependencies
- App\Livewire\Forms\RequisitionsForm
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- Profile
- RequestsTest.php
- StockMonitoringTest.php
- InventoryBatchesFactory.php
- InventoryAdjustments
- App\Livewire\Requisitions\Requests
- TransactionHistoryTest.php
- Composer Runtime Deps
- Livewire\Component
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- App\Livewire\Monitoring\ExpirationMonitoring
- Illuminate\Database\Eloquent\Model
- Items
- InventoryAdjustmentsFactory
- Composer Config
- StockMetrics
- Illuminate\Database\Eloquent\Factories\Factory
- RequestForm
- approvals.blade
- composer
- composer (2)
- UserFactory
- table
- table (2)
- Illuminate\Database\Eloquent\Model
- stock-monitoring.blade
- expiration-monitoring.blade
- table (4)
- StockReceipts
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
- ExpirationMonitoringTest.php
- requests.blade.php
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- receiving-create-form.blade
- stocks.blade
- App\Livewire\Inventory\Receiving
- TrackRequest
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- sidebar.blade
- card.blade
- simple.blade
- split.blade
- BatchCodeGenerator
- request-form.blade.php
- storage-locations.blade.php
- StockReceiptsFactory
- categories.blade
- items.blade
- appearance.blade
- Illuminate\Support\Carbon
- AdministrativeAreaService.php
- Pest.php
- RequestEmailNotificationsTest.php
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- InventoryBatchesFactory
- public-portal.blade.php
- emails/requisition-received.blade.php
- guest.blade.php
- emails/requisition-status-updated.blade.php
- .apply
- RecoveryCodes
- StorageLocations
- InventoryItemsFactory

## God Nodes (most connected - your core abstractions)
1. `InventoryBatches` - 52 edges
2. `AdjustmentType` - 44 edges
3. `InventoryItems` - 40 edges
4. `User` - 39 edges
5. `Requisitions` - 32 edges
6. `InventoryTransactionType` - 31 edges
7. `RequisitionStatus` - 30 edges
8. `StockReceipts` - 24 edges
9. `Reports` - 23 edges
10. `InventoryTransactions` - 22 edges

## Surprising Connections (you probably didn't know these)
- `{closure#7}()` --calls--> `RequisitionReceived`  [EXTRACTED]
  tests/Feature/Requisitions/MailViewsTest.php → app/Notifications/RequisitionReceived.php
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --calls--> `RequisitionReceived`  [EXTRACTED]
  tests/Feature/Requisitions/MailViewsTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (232 total, 170 thin omitted)

### Community 0 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.10
Nodes (15): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#22}(), {closure#23}(), {closure#25}() (+7 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "DashboardTest.php"
Cohesion: 0.16
Nodes (20): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#8}() (+12 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.13
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#6}(), adjust() (+8 more)

### Community 7 - "InventoryBatches"
Cohesion: 0.25
Nodes (8): InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 8 - "bootstrap/app.php"
Cohesion: 0.14
Nodes (13): {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+5 more)

### Community 9 - "ProfileValidationRules"
Cohesion: 0.21
Nodes (4): CreateNewUser, ResetUserPassword, PasswordValidationRules, DeleteUserForm

### Community 10 - "Item Create/Edit Forms"
Cohesion: 0.18
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 12 - "Transaction History Filters"
Cohesion: 0.13
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 14 - "ReceivingCreateForm"
Cohesion: 0.15
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 17 - "Issuances Tests"
Cohesion: 0.22
Nodes (14): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+6 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "Database Seeders"
Cohesion: 0.13
Nodes (6): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 20 - "DashboardTest.php"
Cohesion: 0.21
Nodes (17): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#15}(), {closure#4}(), {closure#5}(), {closure#6}() (+9 more)

### Community 21 - "MailViewsTest.php"
Cohesion: 0.23
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}(), sendAndCapture()

### Community 24 - "Inventory Adjustment Form"
Cohesion: 0.16
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 26 - "Service Providers"
Cohesion: 0.16
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 27 - "ReceiptStatus"
Cohesion: 0.29
Nodes (3): ReceiptStatus, Complete, Partial

### Community 31 - "InventoryBatches"
Cohesion: 0.15
Nodes (13): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+5 more)

### Community 32 - "Requisitions"
Cohesion: 0.06
Nodes (21): ApprovalDecision, Approved, Rejected, ApprovalsForm, {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}() (+13 more)

### Community 33 - "App\Models\Requisitions"
Cohesion: 0.21
Nodes (4): {closure#1}(), {closure#2}(), RequisitionReceived, RequisitionStatusUpdated

### Community 35 - "JS Charts"
Cohesion: 0.19
Nodes (9): arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num(), r2() (+1 more)

### Community 36 - "Stock Status Enum"
Cohesion: 0.15
Nodes (6): StockStatus, Critical, Healthy, Out, Reorder, {closure#5}()

### Community 37 - "ReportsTest.php"
Cohesion: 0.14
Nodes (16): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#10}(), {closure#2}(), {closure#4}() (+8 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Livewire\Form"
Cohesion: 0.07
Nodes (6): StorageLocationForm, InventoryBatchesForm, InventoryCategoriesForm, InventoryTransactionsForm, StorageLocationsForm, CategoryCreateForm

### Community 43 - "Breadcrumbs Header"
Cohesion: 0.27
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 57 - "InventoryAdjustments"
Cohesion: 0.11
Nodes (6): InventoryAdjustments, StockIssuances, {closure#1}(), {closure#3}(), {closure#4}(), TransactionReferences

### Community 59 - "TransactionHistoryTest.php"
Cohesion: 0.31
Nodes (6): {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 61 - "Livewire\Component"
Cohesion: 0.16
Nodes (3): AuditLogs, PublicPortal, Appearance

### Community 64 - "App\Livewire\Monitoring\ExpirationMonitoring"
Cohesion: 0.29
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}()

### Community 65 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (8): {closure#2}(), InventoryCategories, InventoryItems, StorageLocations, Suppliers, {closure#1}(), {closure#1}(), {closure#2}()

### Community 69 - "InventoryAdjustmentsFactory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "StockMetrics"
Cohesion: 0.15
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), StockMetrics

### Community 72 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.27
Nodes (3): InventoryCategoriesFactory, StorageLocationsFactory, SuppliersFactory

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 81 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.08
Nodes (6): Approvals, AuditLogs, InventoryTransactions, RequisitionItems, StockIssuanceBatches, StockIssuanceItems

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

### Community 106 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

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
Cohesion: 0.40
Nodes (4): configurations.storage-location-form, delete, partials.configuration-heading, sort

### Community 198 - "AdministrativeAreaService.php"
Cohesion: 0.15
Nodes (6): {closure#1}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 200 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

## Knowledge Gaps
- **104 isolated node(s):** `emails.layout`, `emails.partials.button`, `emails.layout`, `emails.partials.button`, `resendVerificationNotification` (+99 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 691 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **170 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User Model & Auth` to `InventoryBatches`, `ProfileValidationRules`, `Database Seeders`, `ReceiptStatus`, `Requisitions`, `StockReceiptsForm.php`, `ReportsTest.php`, `Breadcrumbs Header`, `EmailVerificationTest.php`, `StockMonitoringTest.php`, `Security Tests`, `TransactionHistoryTest.php`, `Illuminate\Database\Eloquent\Model`, `InventoryAdjustmentsFactory`, `PasswordResetTest.php`, `UserFactory`, `Illuminate\Database\Eloquent\Model`, `Livewire\Livewire`, `Laravel\Fortify\Features`, `.apply`, `ExpirationMonitoringTest.php`?**
  _High betweenness centrality (0.072) - this node is a cross-community bridge._
- **What connects `emails.layout`, `emails.partials.button`, `emails.layout` to the rest of the system?**
  _104 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App\Livewire\Monitoring\StockMonitoring` be split into smaller, more focused modules?**
  _Cohesion score 0.09666666666666666 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `InventoryBatches` to `App\Livewire\Monitoring\StockMonitoring`, `DashboardTest.php`, `Stocks Browser`, `Inventory Adjustment Form`, `InventoryBatches`, `Requisitions`, `StockReceiptsForm.php`, `ReportsTest.php`, `Reports.php`, `Livewire\Form`, `Illuminate\Support\Carbon`, `StockMonitoringTest.php`, `InventoryBatchesFactory.php`, `TransactionHistoryTest.php`, `App\Livewire\Monitoring\ExpirationMonitoring`, `Illuminate\Database\Eloquent\Model`, `InventoryAdjustmentsFactory`, `Illuminate\Database\Eloquent\Model`, `.apply`, `ExpirationMonitoringTest.php`?**
  _High betweenness centrality (0.063) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `Illuminate\Database\Eloquent\Model` to `Requisitions`, `App\Livewire\Monitoring\StockMonitoring`, `StockReceiptsForm.php`, `BatchAllocator`, `Livewire\WithPagination`, `ReportsTest.php`, `Livewire\Form`, `Item Create/Edit Forms`, `App\Livewire\Requisitions\Issuances`, `Illuminate\Support\Carbon`, `ExpirationMonitoringTest.php`, `Illuminate\Database\Eloquent\Model`, `Database Seeders`, `StockMonitoringTest.php`, `InventoryBatchesFactory.php`, `TransactionHistoryTest.php`, `Stock Monitoring Dashboard`, `InventoryBatches`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.12923076923076923 - nodes in this community are weakly interconnected._