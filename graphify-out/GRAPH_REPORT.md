# Graph Report - inventory-system-prototype  (2026-11-24)

## Corpus Check
- 267 files · ~63,954 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 17, .example 1, .neon 1)

## Summary
- 1650 nodes · 2992 edges · 217 communities (62 shown, 155 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 71 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `62b29514`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- App\Livewire\Monitoring\StockMonitoring
- InventoryItems
- Frontend NPM Dependencies
- DashboardTest.php
- AdjustmentType
- Categories & Items Lists
- Issuance Workflow
- ExpirationMonitoringTest.php
- Fortify Views & Bootstrap
- ProfileValidationRules
- Item Create/Edit Forms
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- App\Models\InventoryItems
- Security Settings Component
- Illuminate\Database\Eloquent\Model
- Issuances Tests
- Illuminate\Database\Schema\Blueprint
- Database Seeders
- RequestForm
- StockReceiptsForm.php
- Stocks Browser
- Inventory Adjustment Form
- Expiration Monitoring
- Service Providers
- StockReceipts
- Fortify User Actions
- Stock Monitoring Dashboard
- User Model & Auth
- InventoryBatches
- Requisitions
- App\Models\Requisitions
- App\Models\StockIssuances
- JS Charts
- Stock Status Enum
- ReportsTest.php
- Dashboard
- Composer Scripts
- App\Models\StorageLocations
- Adjustments List
- Breadcrumbs Header
- Composer Metadata
- Composer Dev Dependencies
- App\Livewire\Forms\RequisitionsForm
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- Profile
- App\Livewire\Forms\IssuancesForm
- RequestsTest.php
- StockMonitoringTest.php
- Illuminate\Database\Eloquent\Factories\Factory
- InventoryAdjustments
- Requests
- TransactionHistoryTest.php
- Composer Runtime Deps
- Livewire\Component
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- InventoryTransactionType
- InventoryCategories
- Attachments.php
- Livewire\Form
- Composer Config
- App\Models\InventoryTransactions
- RequestForm
- approvals.blade
- composer
- composer (2)
- UserFactory
- table
- table (2)
- RequestEmailNotificationsTest.php
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
- Welcome to the Inventory System Team
- requests.blade.php
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- receiving-create-form.blade
- stocks.blade
- InventoryCategoriesForm
- RecoveryCodes
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
- StorageLocations
- categories.blade
- items.blade
- appearance.blade
- TrackRequest
- InventoryTransactions
- AdministrativeAreaService.php
- Pest.php
- .request
- CLAUDE.md
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- public-portal.blade.php
- guest.blade.php
- .apply

## God Nodes (most connected - your core abstractions)
1. `InventoryBatches` - 52 edges
2. `AdjustmentType` - 44 edges
3. `InventoryItems` - 40 edges
4. `User` - 39 edges
5. `Requisitions` - 32 edges
6. `InventoryTransactionType` - 31 edges
7. `RequisitionStatus` - 30 edges
8. `Reports` - 24 edges
9. `StockReceipts` - 24 edges
10. `InventoryTransactions` - 22 edges

## Surprising Connections (you probably didn't know these)
- `{closure#4}()` --references--> `InventoryBatches`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Models/InventoryBatches.php
- `{closure#7}()` --references--> `InventoryBatches`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Models/InventoryBatches.php
- `{closure#6}()` --references--> `InventoryBatches`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Models/InventoryBatches.php
- `{closure#8}()` --references--> `InventoryBatches`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Models/InventoryBatches.php
- `dashBatch()` --references--> `InventoryBatches`  [EXTRACTED]
  tests/Feature/DashboardTest.php → app/Models/InventoryBatches.php

## Import Cycles
- None detected.

## Communities (217 total, 155 thin omitted)

### Community 0 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.11
Nodes (9): {closure#1}(), {closure#10}(), {closure#12}(), {closure#2}(), {closure#25}(), {closure#26}(), {closure#3}(), {closure#6}() (+1 more)

### Community 1 - "InventoryItems"
Cohesion: 0.14
Nodes (6): InventoryItems, StorageLocations, BatchAllocator, {closure#1}(), {closure#2}(), InventoryBatchesSeeder

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "DashboardTest.php"
Cohesion: 0.09
Nodes (37): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#8}() (+29 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.07
Nodes (27): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#6}(), {closure#1}() (+19 more)

### Community 6 - "Issuance Workflow"
Cohesion: 0.08
Nodes (3): IssuancesForm, PublicPortal, Issuances

### Community 7 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 8 - "Fortify Views & Bootstrap"
Cohesion: 0.14
Nodes (13): {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+5 more)

### Community 9 - "ProfileValidationRules"
Cohesion: 0.26
Nodes (3): CreateNewUser, ResetUserPassword, PasswordValidationRules

### Community 10 - "Item Create/Edit Forms"
Cohesion: 0.18
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 12 - "Transaction History Filters"
Cohesion: 0.15
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 14 - "App\Models\InventoryItems"
Cohesion: 0.14
Nodes (12): {closure#11}(), {closure#13}(), {closure#14}(), {closure#7}(), {closure#9}(), {closure#1}(), {closure#2}(), {closure#3}() (+4 more)

### Community 16 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.08
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
Cohesion: 0.14
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 24 - "Inventory Adjustment Form"
Cohesion: 0.17
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 26 - "Service Providers"
Cohesion: 0.14
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 27 - "StockReceipts"
Cohesion: 0.15
Nodes (5): ReceiptStatus, Complete, Partial, StockReceipts, {closure#2}()

### Community 31 - "InventoryBatches"
Cohesion: 0.13
Nodes (17): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#22}(), {closure#23}(), {closure#1}() (+9 more)

### Community 32 - "Requisitions"
Cohesion: 0.06
Nodes (20): ApprovalDecision, Approved, Rejected, ApprovalsForm, {closure#1}(), {closure#2}(), {closure#3}(), Approvals (+12 more)

### Community 33 - "App\Models\Requisitions"
Cohesion: 0.21
Nodes (4): {closure#1}(), {closure#2}(), RequisitionReceived, RequisitionStatusUpdated

### Community 34 - "App\Models\StockIssuances"
Cohesion: 0.24
Nodes (4): StockIssuances, {closure#1}(), {closure#3}(), TransactionReferences

### Community 35 - "JS Charts"
Cohesion: 0.19
Nodes (9): arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num(), r2() (+1 more)

### Community 36 - "Stock Status Enum"
Cohesion: 0.15
Nodes (6): StockStatus, Critical, Healthy, Out, Reorder, {closure#5}()

### Community 37 - "ReportsTest.php"
Cohesion: 0.26
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 43 - "Breadcrumbs Header"
Cohesion: 0.20
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

### Community 55 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.07
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, InventoryItemsFactory, StockReceiptsFactory, StorageLocationsFactory (+1 more)

### Community 57 - "InventoryAdjustments"
Cohesion: 0.19
Nodes (3): InventoryAdjustments, Suppliers, {closure#4}()

### Community 59 - "TransactionHistoryTest.php"
Cohesion: 0.26
Nodes (8): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyEntry(), historyIds()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 61 - "Livewire\Component"
Cohesion: 0.13
Nodes (3): Receiving, AuditLogs, Appearance

### Community 64 - "InventoryTransactionType"
Cohesion: 0.20
Nodes (5): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}()

### Community 65 - "InventoryCategories"
Cohesion: 0.24
Nodes (3): {closure#2}(), InventoryCategories, {closure#1}()

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "App\Models\InventoryTransactions"
Cohesion: 0.20
Nodes (4): {closure#2}(), {closure#7}(), {closure#1}(), StockMetrics

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 81 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

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

### Community 106 - "Welcome to the Inventory System Team"
Cohesion: 0.22
Nodes (8): Codebases, Get Started, How We Use Claude, MCP Servers to Activate, Skills to Know About, Team Tips, Welcome to the Inventory System Team, Your Setup Checklist

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

## Knowledge Gaps
- **100 isolated node(s):** `resendVerificationNotification`, `partials.settings-heading`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+95 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 685 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **155 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User Model & Auth` to `AdjustmentType`, `ExpirationMonitoringTest.php`, `ProfileValidationRules`, `Illuminate\Database\Eloquent\Model`, `Database Seeders`, `StockReceipts`, `Requisitions`, `ReportsTest.php`, `Breadcrumbs Header`, `EmailVerificationTest.php`, `App\Livewire\Forms\IssuancesForm`, `StockMonitoringTest.php`, `Security Tests`, `InventoryAdjustments`, `TransactionHistoryTest.php`, `PasswordResetTest.php`, `UserFactory`, `Livewire\Livewire`, `Laravel\Fortify\Features`, `.apply`?**
  _High betweenness centrality (0.048) - this node is a cross-community bridge._
- **What connects `resendVerificationNotification`, `partials.settings-heading`, `settings.delete-user-form` to the rest of the system?**
  _100 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App\Livewire\Monitoring\StockMonitoring` be split into smaller, more focused modules?**
  _Cohesion score 0.1111111111111111 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `Requisitions`, `InventoryCategories`, `Categories & Items Lists`, `ReportsTest.php`, `ExpirationMonitoringTest.php`, `Item Create/Edit Forms`, `App\Models\InventoryItems`, `Illuminate\Database\Eloquent\Model`, `Database Seeders`, `App\Livewire\Forms\IssuancesForm`, `StockReceiptsForm.php`, `StockMonitoringTest.php`, `Illuminate\Database\Eloquent\Factories\Factory`, `InventoryAdjustments`, `TransactionHistoryTest.php`, `Stock Monitoring Dashboard`, `InventoryBatches`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **Should `InventoryItems` be split into smaller, more focused modules?**
  _Cohesion score 0.1422924901185771 - nodes in this community are weakly interconnected._
- **Why does `StockMonitoring` connect `Stock Monitoring Dashboard` to `App\Livewire\Monitoring\StockMonitoring`, `DashboardTest.php`, `Categories & Items Lists`, `.request`, `web.php`, `StockMonitoringTest.php`, `Livewire\Component`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._