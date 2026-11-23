# Graph Report - inventory-system-prototype  (2026-11-23)

## Corpus Check
- 260 files · ~59,838 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 17, .example 1, .neon 1)

## Summary
- 1577 nodes · 2814 edges · 222 communities (62 shown, 160 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 74 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `4fba6262`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Requisitions
- Illuminate\Support\Carbon
- Frontend NPM Dependencies
- StatusNotificationsTest.php
- Adjustment Types
- Categories & Items Lists
- Issuance Workflow
- web.php
- Fortify Views & Bootstrap
- InventoryTransactionType
- Item Create/Edit Forms
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- ReportsTest.php
- Security Settings Component
- Illuminate\Database\Eloquent\Model
- Issuances Tests
- Illuminate\Database\Schema\Blueprint
- Database Seeders
- Livewire\Component
- StockReceiptsForm.php
- Stocks Browser
- Inventory Adjustment Form
- Expiration Monitoring
- Service Providers
- Approvals Workflow
- Fortify User Actions
- Stock Monitoring Dashboard
- User Model & Auth
- App\Livewire\Monitoring\ExpirationMonitoring
- Approvals Tests
- StockReceipts
- Illuminate\Database\Eloquent\Builder
- JS Charts
- Stock Status Enum
- Reports Closures
- Inventory Batches Model
- Composer Scripts
- TransactionHistoryTest.php
- Adjustments List
- Breadcrumbs Header
- Composer Metadata
- Composer Dev Dependencies
- PasswordResetTest.php
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- Profile
- InventoryTransactions
- RequestsTest.php
- StockMonitoringTest.php
- Illuminate\Database\Eloquent\Factories\Factory
- Inventory Adjustments Model
- .view
- RequestEmailNotificationsTest.php
- Composer Runtime Deps
- App\Livewire\Inventory\Receiving
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- ApprovalDecision
- InventoryItems
- Two-Factor Recovery Codes
- Attachments.php
- App\Models\StorageLocations
- Composer Config
- ApprovalsForm
- Logout Action
- RequestForm
- approvals.blade
- composer
- composer (2)
- UserFactory
- table
- table (2)
- InventoryAdjustmentsFactory
- stock-monitoring.blade
- expiration-monitoring.blade
- table (4)
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
- InventoryBatchesFactory.php
- requests.blade.php
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- receiving-create-form.blade
- stocks.blade
- InventoryCategoriesForm
- Requisitions
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- sidebar.blade
- card.blade
- simple.blade
- split.blade
- InventoryBatchesFactory
- request-form.blade.php
- storage-locations.blade.php
- StorageLocations
- categories.blade
- items.blade
- appearance.blade
- BatchCodeGenerator
- AdministrativeAreaService.php
- Pest.php
- ReceiptStatus
- TrackRequest
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- InventoryCategories
- public-portal.blade.php
- guest.blade.php
- StockReceiptsFactory
- .apply
- InventoryItemsFactory

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 53 edges
2. `AdjustmentType` - 46 edges
3. `InventoryBatches` - 46 edges
4. `User` - 41 edges
5. `Requisitions` - 34 edges
6. `InventoryTransactionType` - 32 edges
7. `RequisitionStatus` - 28 edges
8. `InventoryTransactions` - 27 edges
9. `StockReceipts` - 26 edges
10. `Reports` - 24 edges

## Surprising Connections (you probably didn't know these)
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#1}()` --references--> `InventoryItems`  [EXTRACTED]
  database/seeders/InventoryBatchesSeeder.php → app/Models/InventoryItems.php
- `{closure#2}()` --references--> `InventoryItems`  [EXTRACTED]
  database/seeders/InventoryBatchesSeeder.php → app/Models/InventoryItems.php

## Import Cycles
- None detected.

## Communities (222 total, 160 thin omitted)

### Community 0 - "Requisitions"
Cohesion: 0.21
Nodes (4): {closure#1}(), {closure#2}(), RequisitionReceived, RequisitionStatusUpdated

### Community 1 - "Illuminate\Support\Carbon"
Cohesion: 0.36
Nodes (3): {closure#1}(), {closure#2}(), InventoryBatchesSeeder

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "StatusNotificationsTest.php"
Cohesion: 0.16
Nodes (20): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#9}() (+12 more)

### Community 4 - "Adjustment Types"
Cohesion: 0.13
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#7}(), adjust() (+8 more)

### Community 8 - "Fortify Views & Bootstrap"
Cohesion: 0.23
Nodes (6): {closure#10}(), {closure#8}(), {closure#9}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 9 - "InventoryTransactionType"
Cohesion: 0.16
Nodes (8): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#26}(), {closure#4}(), {closure#1}(), historyEntry()

### Community 10 - "Item Create/Edit Forms"
Cohesion: 0.19
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 11 - "Stock Levels & Admin Areas"
Cohesion: 0.20
Nodes (6): {closure#10}(), {closure#12}(), {closure#25}(), {closure#6}(), {closure#8}(), AdministrativeAreaService

### Community 14 - "ReportsTest.php"
Cohesion: 0.26
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 16 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (5): Approvals, AuditLogs, RequisitionItems, StockIssuanceBatches, StockIssuanceItems

### Community 17 - "Issuances Tests"
Cohesion: 0.22
Nodes (14): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+6 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "Database Seeders"
Cohesion: 0.13
Nodes (6): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 21 - "StockReceiptsForm.php"
Cohesion: 0.15
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 24 - "Inventory Adjustment Form"
Cohesion: 0.16
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 25 - "Expiration Monitoring"
Cohesion: 0.12
Nodes (12): {closure#5}(), ExpirationMonitoring, {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+4 more)

### Community 26 - "Service Providers"
Cohesion: 0.16
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 28 - "Fortify User Actions"
Cohesion: 0.22
Nodes (4): CreateNewUser, ResetUserPassword, PasswordValidationRules, DeleteUserForm

### Community 31 - "App\Livewire\Monitoring\ExpirationMonitoring"
Cohesion: 0.17
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}()

### Community 32 - "Approvals Tests"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 33 - "StockReceipts"
Cohesion: 0.12
Nodes (5): {closure#4}(), {closure#9}(), {closure#1}(), StockReceipts, Suppliers

### Community 34 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.12
Nodes (15): {closure#1}(), {closure#1}(), {closure#10}(), {closure#13}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}() (+7 more)

### Community 35 - "JS Charts"
Cohesion: 0.19
Nodes (9): arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num(), r2() (+1 more)

### Community 36 - "Stock Status Enum"
Cohesion: 0.15
Nodes (5): StockStatus, Critical, Healthy, Out, Reorder

### Community 38 - "Reports Closures"
Cohesion: 0.15
Nodes (4): {closure#11}(), {closure#12}(), {closure#13}(), {closure#2}()

### Community 39 - "Inventory Batches Model"
Cohesion: 0.21
Nodes (10): {closure#22}(), {closure#23}(), InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}() (+2 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "TransactionHistoryTest.php"
Cohesion: 0.24
Nodes (7): StorageLocations, {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

### Community 43 - "Breadcrumbs Header"
Cohesion: 0.26
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

### Community 52 - "InventoryTransactions"
Cohesion: 0.11
Nodes (5): {closure#1}(), InventoryBatchesForm, InventoryTransactionsForm, {closure#7}(), InventoryTransactions

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 55 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.27
Nodes (3): InventoryCategoriesFactory, StorageLocationsFactory, SuppliersFactory

### Community 58 - ".view"
Cohesion: 0.15
Nodes (8): Requests, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 59 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 64 - "ApprovalDecision"
Cohesion: 0.20
Nodes (6): ApprovalDecision, Approved, Rejected, {closure#2}(), {closure#3}(), {closure#1}()

### Community 65 - "InventoryItems"
Cohesion: 0.09
Nodes (12): {closure#2}(), {closure#11}(), {closure#13}(), {closure#14}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#7}() (+4 more)

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

### Community 81 - "InventoryAdjustmentsFactory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

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
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 200 - "ReceiptStatus"
Cohesion: 0.29
Nodes (3): ReceiptStatus, Complete, Partial

## Knowledge Gaps
- **100 isolated node(s):** `startOver`, `track`, `edit`, `submit`, `addItem` (+95 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 657 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **160 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User Model & Auth` to `InventoryTransactionType`, `ReportsTest.php`, `Database Seeders`, `Expiration Monitoring`, `Fortify User Actions`, `Approvals Tests`, `StockReceipts`, `Illuminate\Database\Eloquent\Builder`, `Inventory Batches Model`, `TransactionHistoryTest.php`, `Breadcrumbs Header`, `PasswordResetTest.php`, `EmailVerificationTest.php`, `StockMonitoringTest.php`, `Security Tests`, `ReceiptStatus`, `ProfileValidationRules.php`, `UserFactory`, `InventoryAdjustmentsFactory`, `Livewire\Livewire`, `Laravel\Fortify\Features`, `.apply`, `DashboardTest.php`, `Requisitions`?**
  _High betweenness centrality (0.061) - this node is a cross-community bridge._
- **What connects `startOver`, `track`, `edit` to the rest of the system?**
  _100 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `Requisitions` connect `Requisitions` to `ApprovalDecision`, `Requisitions`, `Approvals Tests`, `Reports Closures`, `Issuance Workflow`, `ApprovalsForm`, `TransactionHistoryTest.php`, `Illuminate\Database\Eloquent\Model`, `Issuances Tests`, `InventoryTransactions`, `RequestsTest.php`, `Livewire\Component`, `StockMonitoringTest.php`, `TrackRequestTest.php`, `.view`, `Approvals Workflow`?**
  _High betweenness centrality (0.040) - this node is a cross-community bridge._
- **Should `Adjustment Types` be split into smaller, more focused modules?**
  _Cohesion score 0.12923076923076923 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `Approvals Tests`, `StockReceipts`, `Illuminate\Database\Eloquent\Builder`, `Illuminate\Support\Carbon`, `Categories & Items Lists`, `Reports Closures`, `TransactionHistoryTest.php`, `Item Create/Edit Forms`, `InventoryBatchesFactory.php`, `ReportsTest.php`, `Illuminate\Database\Eloquent\Model`, `Database Seeders`, `InventoryTransactions`, `Livewire\Component`, `StockMonitoringTest.php`, `Expiration Monitoring`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Should `Categories & Items Lists` be split into smaller, more focused modules?**
  _Cohesion score 0.12418300653594772 - nodes in this community are weakly interconnected._