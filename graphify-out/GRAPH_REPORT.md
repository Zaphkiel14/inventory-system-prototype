# Graph Report - inventory-system-prototype  (2026-11-23)

## Corpus Check
- 254 files · ~58,500 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 17, .example 1, .neon 1)

## Summary
- 1533 nodes · 2683 edges · 216 communities (61 shown, 155 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 72 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `aa4ec7af`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- InventoryTransactions
- Illuminate\Database\Eloquent\Factories\Factory
- Frontend NPM Dependencies
- RequisitionsForm
- Adjustment Types
- Categories & Items Lists
- Issuance Workflow
- web.php
- Fortify Views & Bootstrap
- Stock Monitoring Closures
- Item Create/Edit Forms
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- Usable Batch Scopes
- Security Settings Component
- Requisition & Issuance Models
- Issuances Tests
- Illuminate\Database\Schema\Blueprint
- Database Seeders
- ApprovalDecision
- StockReceiptsForm.php
- Livewire\Component
- Stocks Browser
- Inventory Adjustment Form
- Expiration Monitoring
- Service Providers
- Approvals Workflow
- Fortify User Actions
- Stock Monitoring Dashboard
- User Model & Auth
- AdjustmentType
- Approvals Tests
- StockReceipts
- IssuancesForm.php
- JS Charts
- Stock Status Enum
- RequisitionsForm.php
- Reports Closures
- Inventory Batches Model
- Composer Scripts
- Requisition Status Enum
- Adjustments List
- Breadcrumbs Header
- Composer Metadata
- Composer Dev Dependencies
- Deploy Workflow
- Expiration Monitoring Tests
- Category Multi-Select
- Security Blade View
- Profile
- Batch/Category/Txn Forms
- RequestsTest.php
- StockMonitoringTest.php
- ReportsTest.php
- Inventory Adjustments Model
- Requests List
- Approvals Model
- Composer Runtime Deps
- InventoryTransactionType
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Requisitions
- InventoryCategories
- Two-Factor Recovery Codes
- Attachments.php
- App\Models\StorageLocations
- Composer Config
- App\Livewire\Monitoring\TransactionHistory
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
- requests.blade.php
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- receiving-create-form.blade
- stocks.blade
- InventoryCategoriesForm
- Approvals.php
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- sidebar.blade
- card.blade
- simple.blade
- split.blade
- Pest.php
- request-form.blade.php
- storage-locations.blade.php
- StorageLocations
- categories.blade
- items.blade
- appearance.blade
- ReceivingCreateForm
- AdministrativeAreaService.php
- Categories
- TrackRequest
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- public-portal.blade.php
- guest.blade.php

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 53 edges
2. `AdjustmentType` - 46 edges
3. `InventoryBatches` - 46 edges
4. `User` - 41 edges
5. `Requisitions` - 38 edges
6. `InventoryTransactionType` - 32 edges
7. `InventoryTransactions` - 27 edges
8. `StockReceipts` - 26 edges
9. `Reports` - 24 edges
10. `ExpirationMonitoring` - 21 edges

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

## Communities (216 total, 155 thin omitted)

### Community 0 - "InventoryTransactions"
Cohesion: 0.26
Nodes (8): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyEntry(), historyIds()

### Community 1 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.05
Nodes (13): BatchCodeGenerator, {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, InventoryItemsFactory, StockReceiptsFactory (+5 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 4 - "Adjustment Types"
Cohesion: 0.23
Nodes (7): adjust(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#9}()

### Community 8 - "Fortify Views & Bootstrap"
Cohesion: 0.14
Nodes (13): {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+5 more)

### Community 9 - "Stock Monitoring Closures"
Cohesion: 0.13
Nodes (9): {closure#11}(), {closure#13}(), {closure#14}(), {closure#22}(), {closure#23}(), {closure#7}(), {closure#9}(), InventoryItems (+1 more)

### Community 10 - "Item Create/Edit Forms"
Cohesion: 0.16
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 11 - "Stock Levels & Admin Areas"
Cohesion: 0.20
Nodes (6): {closure#10}(), {closure#12}(), {closure#25}(), {closure#6}(), {closure#8}(), AdministrativeAreaService

### Community 12 - "Transaction History Filters"
Cohesion: 0.17
Nodes (3): {closure#12}(), {closure#13}(), TransactionHistory

### Community 14 - "Usable Batch Scopes"
Cohesion: 0.15
Nodes (14): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#1}(), {closure#2}(), {closure#3}() (+6 more)

### Community 16 - "Requisition & Issuance Models"
Cohesion: 0.13
Nodes (4): AuditLogs, RequisitionItems, StockIssuanceBatches, StockIssuanceItems

### Community 17 - "Issuances Tests"
Cohesion: 0.22
Nodes (14): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+6 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "Database Seeders"
Cohesion: 0.13
Nodes (6): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 20 - "ApprovalDecision"
Cohesion: 0.33
Nodes (3): ApprovalDecision, Approved, Rejected

### Community 24 - "Inventory Adjustment Form"
Cohesion: 0.16
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 26 - "Service Providers"
Cohesion: 0.16
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 28 - "Fortify User Actions"
Cohesion: 0.26
Nodes (3): CreateNewUser, ResetUserPassword, PasswordValidationRules

### Community 31 - "AdjustmentType"
Cohesion: 0.18
Nodes (9): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#7}(), {closure#6}() (+1 more)

### Community 32 - "Approvals Tests"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 33 - "StockReceipts"
Cohesion: 0.10
Nodes (8): ReceiptStatus, Complete, Partial, {closure#4}(), {closure#9}(), StockReceipts, StorageLocations, Suppliers

### Community 34 - "IssuancesForm.php"
Cohesion: 0.13
Nodes (4): {closure#1}(), {closure#10}(), {closure#5}(), StockIssuances

### Community 35 - "JS Charts"
Cohesion: 0.19
Nodes (9): arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num(), r2() (+1 more)

### Community 36 - "Stock Status Enum"
Cohesion: 0.14
Nodes (6): StockStatus, Critical, Healthy, Out, Reorder, {closure#5}()

### Community 38 - "Reports Closures"
Cohesion: 0.15
Nodes (4): {closure#11}(), {closure#12}(), {closure#13}(), {closure#2}()

### Community 39 - "Inventory Batches Model"
Cohesion: 0.25
Nodes (8): InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Requisition Status Enum"
Cohesion: 0.20
Nodes (8): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#9}()

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

### Community 48 - "Expiration Monitoring Tests"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 55 - "ReportsTest.php"
Cohesion: 0.26
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 57 - "Inventory Adjustments Model"
Cohesion: 0.24
Nodes (3): {closure#11}(), InventoryAdjustments, InventoryAdjustmentsSeeder

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 61 - "InventoryTransactionType"
Cohesion: 0.18
Nodes (6): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#26}(), {closure#4}()

### Community 64 - "Requisitions"
Cohesion: 0.19
Nodes (5): ApprovalsForm, {closure#1}(), {closure#2}(), {closure#3}(), Requisitions

### Community 65 - "InventoryCategories"
Cohesion: 0.32
Nodes (3): {closure#2}(), InventoryCategories, {closure#1}()

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "App\Livewire\Monitoring\TransactionHistory"
Cohesion: 0.15
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}(), {closure#8}(), InventoryTransactions

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
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

## Knowledge Gaps
- **100 isolated node(s):** `partials.head`, `partials.head`, `partials.configuration-heading`, `configurations.storage-location-form`, `sort` (+95 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 649 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **155 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User Model & Auth` to `InventoryTransactions`, `Database Seeders`, `Fortify User Actions`, `Approvals Tests`, `StockReceipts`, `Inventory Batches Model`, `Breadcrumbs Header`, `PasswordResetTest.php`, `Expiration Monitoring Tests`, `StockMonitoringTest.php`, `ReportsTest.php`, `Security Tests`, `Inventory Adjustments Model`, `Requisitions`, `App\Livewire\Monitoring\TransactionHistory`, `AuthenticationTest.php`, `ProfileValidationRules.php`, `DashboardTest.php`, `InventoryAdjustmentsFactory`, `Livewire\Livewire`, `EmailVerificationTest.php`, `Illuminate\Support\Str`?**
  _High betweenness centrality (0.061) - this node is a cross-community bridge._
- **What connects `partials.head`, `partials.head`, `partials.configuration-heading` to the rest of the system?**
  _100 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Eloquent\Factories\Factory` be split into smaller, more focused modules?**
  _Cohesion score 0.05026300409117475 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `Stock Monitoring Closures` to `InventoryTransactions`, `Illuminate\Database\Eloquent\Factories\Factory`, `Categories & Items Lists`, `Item Create/Edit Forms`, `Usable Batch Scopes`, `Requisition & Issuance Models`, `Database Seeders`, `StockReceiptsForm.php`, `Livewire\Component`, `Stock Monitoring Dashboard`, `Approvals Tests`, `StockReceipts`, `Stock Status Enum`, `Reports Closures`, `Expiration Monitoring Tests`, `Batch/Category/Txn Forms`, `StockMonitoringTest.php`, `ReportsTest.php`, `Requisitions`, `InventoryCategories`?**
  _High betweenness centrality (0.040) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `Inventory Batches Model` to `InventoryTransactions`, `StockReceipts`, `Illuminate\Database\Eloquent\Factories\Factory`, `Approvals Tests`, `Adjustment Types`, `Reports Closures`, `Stock Monitoring Closures`, `Usable Batch Scopes`, `Requisition & Issuance Models`, `InventoryAdjustmentsFactory`, `Expiration Monitoring Tests`, `Batch/Category/Txn Forms`, `StockReceiptsForm.php`, `ReportsTest.php`, `Stocks Browser`, `Inventory Adjustment Form`, `Inventory Adjustments Model`, `StockMonitoringTest.php`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Should `Categories & Items Lists` be split into smaller, more focused modules?**
  _Cohesion score 0.11695906432748537 - nodes in this community are weakly interconnected._