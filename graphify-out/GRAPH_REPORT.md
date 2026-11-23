# Graph Report - inventory-system-prototype  (2026-11-23)

## Corpus Check
- 262 files · ~60,973 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 17, .example 1, .neon 1)

## Summary
- 1591 nodes · 2838 edges · 207 communities (57 shown, 150 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 74 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `56e3e663`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- App\Livewire\Monitoring\StockMonitoring
- Illuminate\Support\Carbon
- Frontend NPM Dependencies
- StatusNotificationsTest.php
- Adjustment Types
- Categories & Items Lists
- Issuance Workflow
- ExpirationMonitoringTest.php
- Fortify Views & Bootstrap
- ProfileValidationRules
- Item Create/Edit Forms
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- AdjustmentType
- Security Settings Component
- Illuminate\Database\Eloquent\Model
- Issuances Tests
- Illuminate\Database\Schema\Blueprint
- Database Seeders
- RequestForm
- StockReceiptsForm.php
- Livewire\Component
- Stocks Browser
- Inventory Adjustment Form
- Expiration Monitoring
- Service Providers
- StockReceipts
- Fortify User Actions
- Stock Monitoring Dashboard
- User Model & Auth
- Illuminate\Database\Eloquent\Builder
- Requisitions
- Illuminate\Database\Eloquent\Relations\HasMany
- Illuminate\Database\Eloquent\Builder
- JS Charts
- Stock Status Enum
- Reports Closures
- Inventory Batches Model
- Composer Scripts
- Adjustments List
- Breadcrumbs Header
- Composer Metadata
- Composer Dev Dependencies
- App\Livewire\Forms\RequisitionsForm
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- Profile
- InventoryTransactions
- RequestsTest.php
- InventoryTransactionType
- Illuminate\Database\Eloquent\Factories\Factory
- Inventory Adjustments Model
- Requests
- Composer Runtime Deps
- App\Livewire\Inventory\Receiving
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Approvals
- InventoryItems
- Attachments.php
- Livewire\Form
- Composer Config
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
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- sidebar.blade
- card.blade
- simple.blade
- split.blade
- request-form.blade.php
- storage-locations.blade.php
- StorageLocations
- categories.blade
- items.blade
- appearance.blade
- AdministrativeAreaService.php
- Pest.php
- ReceiptStatus
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- public-portal.blade.php
- guest.blade.php
- .apply

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 53 edges
2. `InventoryBatches` - 46 edges
3. `AdjustmentType` - 46 edges
4. `User` - 41 edges
5. `Requisitions` - 33 edges
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
- `{closure#6}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  tests/Feature/Inventory/AdjustmentsTest.php → app/Enums/AdjustmentType.php
- `{closure#8}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  tests/Feature/Inventory/AdjustmentsTest.php → app/Enums/AdjustmentType.php

## Import Cycles
- None detected.

## Communities (207 total, 150 thin omitted)

### Community 0 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.11
Nodes (10): {closure#1}(), {closure#10}(), {closure#12}(), {closure#2}(), {closure#22}(), {closure#23}(), {closure#25}(), {closure#3}() (+2 more)

### Community 1 - "Illuminate\Support\Carbon"
Cohesion: 0.21
Nodes (4): BatchCodeGenerator, {closure#1}(), {closure#2}(), InventoryBatchesSeeder

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "StatusNotificationsTest.php"
Cohesion: 0.05
Nodes (32): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#9}() (+24 more)

### Community 4 - "Adjustment Types"
Cohesion: 0.18
Nodes (10): {closure#1}(), adjust(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+2 more)

### Community 6 - "Issuance Workflow"
Cohesion: 0.06
Nodes (3): IssuancesForm, PublicPortal, Issuances

### Community 7 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 8 - "Fortify Views & Bootstrap"
Cohesion: 0.14
Nodes (13): {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+5 more)

### Community 10 - "Item Create/Edit Forms"
Cohesion: 0.19
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 14 - "AdjustmentType"
Cohesion: 0.22
Nodes (7): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#7}()

### Community 16 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (3): RequisitionItems, StockIssuanceBatches, StockIssuanceItems

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
Cohesion: 0.14
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 24 - "Inventory Adjustment Form"
Cohesion: 0.17
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 26 - "Service Providers"
Cohesion: 0.14
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 28 - "Fortify User Actions"
Cohesion: 0.20
Nodes (4): ResetUserPassword, PasswordValidationRules, Logout, DeleteUserForm

### Community 31 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.22
Nodes (10): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#1}(), {closure#2}(), {closure#3}() (+2 more)

### Community 32 - "Requisitions"
Cohesion: 0.05
Nodes (21): ApprovalDecision, Approved, Rejected, ApprovalsForm, {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}() (+13 more)

### Community 33 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.17
Nodes (3): InventoryCategories, StorageLocations, Suppliers

### Community 34 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.12
Nodes (11): {closure#1}(), {closure#10}(), {closure#12}(), {closure#13}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+3 more)

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
Cohesion: 0.23
Nodes (8): InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

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

### Community 54 - "InventoryTransactionType"
Cohesion: 0.08
Nodes (35): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#26}(), {closure#4}(), {closure#10}(), {closure#2}() (+27 more)

### Community 55 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.07
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, InventoryItemsFactory, StockReceiptsFactory, StorageLocationsFactory (+1 more)

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 65 - "InventoryItems"
Cohesion: 0.14
Nodes (9): {closure#2}(), {closure#11}(), {closure#13}(), {closure#14}(), {closure#7}(), {closure#9}(), InventoryItems, BatchAllocator (+1 more)

### Community 69 - "Livewire\Form"
Cohesion: 0.10
Nodes (6): StorageLocationForm, InventoryBatchesForm, InventoryCategoriesForm, InventoryTransactionsForm, StorageLocationsForm, CategoryCreateForm

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
Cohesion: 0.16
Nodes (5): {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 200 - "ReceiptStatus"
Cohesion: 0.33
Nodes (3): ReceiptStatus, Complete, Partial

## Knowledge Gaps
- **100 isolated node(s):** `startOver`, `track`, `edit`, `submit`, `addItem` (+95 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 662 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **150 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User Model & Auth` to `StatusNotificationsTest.php`, `ExpirationMonitoringTest.php`, `ProfileValidationRules`, `Illuminate\Database\Eloquent\Model`, `Database Seeders`, `Fortify User Actions`, `Requisitions`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Database\Eloquent\Builder`, `Inventory Batches Model`, `Breadcrumbs Header`, `EmailVerificationTest.php`, `InventoryTransactions`, `InventoryTransactionType`, `Security Tests`, `AuthenticationTest.php`, `UserFactory`, `InventoryAdjustmentsFactory`, `Livewire\Livewire`, `Laravel\Fortify\Features`, `.apply`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._
- **What connects `startOver`, `track`, `edit` to the rest of the system?**
  _100 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App\Livewire\Monitoring\StockMonitoring` be split into smaller, more focused modules?**
  _Cohesion score 0.10526315789473684 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `Inventory Batches Model` to `App\Livewire\Monitoring\StockMonitoring`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Support\Carbon`, `Attachments.php`, `Adjustment Types`, `Livewire\Form`, `Reports Closures`, `ExpirationMonitoringTest.php`, `Requisitions`, `Illuminate\Database\Eloquent\Model`, `InventoryAdjustmentsFactory`, `InventoryTransactions`, `Illuminate\Database\Eloquent\Factories\Factory`, `InventoryTransactionType`, `Stocks Browser`, `Inventory Adjustment Form`, `.apply`, `Illuminate\Database\Eloquent\Builder`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `Requisitions`, `App\Livewire\Monitoring\StockMonitoring`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Attachments.php`, `Stock Status Enum`, `App\Services\Inventory\BatchCodeGenerator`, `Categories & Items Lists`, `Reports Closures`, `Illuminate\Support\Carbon`, `ExpirationMonitoringTest.php`, `Item Create/Edit Forms`, `Database Seeders`, `InventoryTransactions`, `StockReceiptsForm.php`, `Livewire\Component`, `Illuminate\Database\Eloquent\Factories\Factory`, `InventoryTransactionType`, `Illuminate\Database\Eloquent\Builder`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Should `StatusNotificationsTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.0518326545723806 - nodes in this community are weakly interconnected._