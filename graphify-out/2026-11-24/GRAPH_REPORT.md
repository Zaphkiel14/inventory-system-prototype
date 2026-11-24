# Graph Report - inventory-system-prototype  (2026-11-24)

## Corpus Check
- 271 files · ~64,809 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 2, .example 1)

## Summary
- 1666 nodes · 3025 edges · 227 communities (64 shown, 163 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 81 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `07b7672b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- App\Livewire\Monitoring\StockMonitoring
- App\Livewire\Monitoring\Reports
- Frontend NPM Dependencies
- DashboardTest.php
- AdjustmentType
- Livewire\WithPagination
- TrackRequestTest.php
- InventoryBatches
- bootstrap/app.php
- ProfileValidationRules
- Item Create/Edit Forms
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- StockMetrics.php
- Security Settings Component
- InventoryItems
- IssuancesTest.php
- Illuminate\Database\Schema\Blueprint
- Database Seeders
- Livewire\Form
- Stocks Browser
- Inventory Adjustment Form
- Expiration Monitoring
- Service Providers
- Illuminate\Support\Carbon
- ReportsTest.php
- Stock Monitoring Dashboard
- User Model & Auth
- Illuminate\Database\Eloquent\Builder
- Requisitions
- App\Models\Requisitions
- Livewire\Component
- JS Charts
- Stock Status Enum
- Illuminate\Database\Eloquent\Factories\Factory
- DashboardTest.php
- Dashboard
- Composer Scripts
- ReceivingCreateForm
- Breadcrumbs Header
- Composer Metadata
- Composer Dev Dependencies
- App\Models\Requisitions
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- Profile
- App\Livewire\Inventory\Items
- RequestsTest.php
- StockMonitoringTest.php
- InventoryAdjustmentsFactory
- RequestForm
- InventoryAdjustments
- Requests
- RecoveryCodes
- Composer Runtime Deps
- MailTemplatesTest.php
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- TransactionHistoryTest.php
- InventoryBatchesFactory
- Composer Config
- UserFactory.php
- InventoryBatchesFactory.php
- RequisitionsForm
- approvals.blade
- composer
- composer (2)
- UserFactory
- table
- table (2)
- Illuminate\Database\Eloquent\Model
- stock-monitoring.blade
- App\Livewire\Monitoring\ExpirationMonitoring
- expiration-monitoring.blade
- ReceiptStatus
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
- Illuminate\Support\Carbon
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- sidebar.blade
- card.blade
- simple.blade
- split.blade
- StorageLocations
- request-form.blade.php
- storage-locations.blade.php
- Logout.php
- categories.blade
- items.blade
- appearance.blade
- StockReceiptsFactory
- AdministrativeAreaService.php
- Pest.php
- RequestEmailNotificationsTest.php
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- App\Services\Inventory\BatchAllocator
- public-portal.blade.php
- .apply
- guest.blade.php
- Attachments.php
- MailLogo.php
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
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#2}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#5}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#6}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#9}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (227 total, 163 thin omitted)

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

### Community 6 - "TrackRequestTest.php"
Cohesion: 0.07
Nodes (4): IssuancesForm, TrackRequest, PublicPortal, Issuances

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
Cohesion: 0.16
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 12 - "Transaction History Filters"
Cohesion: 0.13
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 14 - "StockMetrics.php"
Cohesion: 0.15
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), StockMetrics

### Community 16 - "InventoryItems"
Cohesion: 0.14
Nodes (8): {closure#2}(), InventoryCategories, InventoryItems, StorageLocations, Suppliers, {closure#1}(), {closure#1}(), {closure#2}()

### Community 17 - "IssuancesTest.php"
Cohesion: 0.22
Nodes (14): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+6 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "Database Seeders"
Cohesion: 0.15
Nodes (5): InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 20 - "Livewire\Form"
Cohesion: 0.07
Nodes (6): StorageLocationForm, InventoryBatchesForm, InventoryCategoriesForm, InventoryTransactionsForm, StorageLocationsForm, CategoryCreateForm

### Community 24 - "Inventory Adjustment Form"
Cohesion: 0.16
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 26 - "Service Providers"
Cohesion: 0.16
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 28 - "ReportsTest.php"
Cohesion: 0.14
Nodes (16): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#10}(), {closure#2}(), {closure#4}() (+8 more)

### Community 31 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.15
Nodes (11): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+3 more)

### Community 32 - "Requisitions"
Cohesion: 0.05
Nodes (21): ApprovalDecision, Approved, Rejected, ApprovalsForm, {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}() (+13 more)

### Community 34 - "Livewire\Component"
Cohesion: 0.20
Nodes (3): Receiving, AuditLogs, Appearance

### Community 35 - "JS Charts"
Cohesion: 0.19
Nodes (9): arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num(), r2() (+1 more)

### Community 36 - "Stock Status Enum"
Cohesion: 0.15
Nodes (6): StockStatus, Critical, Healthy, Out, Reorder, {closure#5}()

### Community 37 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.27
Nodes (3): InventoryCategoriesFactory, StorageLocationsFactory, SuppliersFactory

### Community 38 - "DashboardTest.php"
Cohesion: 0.21
Nodes (17): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#15}(), {closure#4}(), {closure#5}(), {closure#6}() (+9 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "ReceivingCreateForm"
Cohesion: 0.12
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 43 - "Breadcrumbs Header"
Cohesion: 0.27
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 46 - "App\Models\Requisitions"
Cohesion: 0.21
Nodes (3): {closure#1}(), {closure#1}(), {closure#2}()

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

### Community 55 - "InventoryAdjustmentsFactory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

### Community 57 - "InventoryAdjustments"
Cohesion: 0.11
Nodes (6): InventoryAdjustments, StockIssuances, {closure#1}(), {closure#3}(), {closure#4}(), TransactionReferences

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 61 - "MailTemplatesTest.php"
Cohesion: 0.18
Nodes (10): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}() (+2 more)

### Community 67 - "TransactionHistoryTest.php"
Cohesion: 0.31
Nodes (6): {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

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

### Community 81 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.08
Nodes (6): Approvals, AuditLogs, InventoryTransactions, RequisitionItems, StockIssuanceBatches, StockIssuanceItems

### Community 82 - "stock-monitoring.blade"
Cohesion: 0.40
Nodes (4): livewire.monitoring.partials.stock-detail, clearFilters, filterStatus, toggle

### Community 83 - "App\Livewire\Monitoring\ExpirationMonitoring"
Cohesion: 0.29
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}()

### Community 84 - "expiration-monitoring.blade"
Cohesion: 0.50
Nodes (3): confirmWriteOff, clearFilters, filterStatus

### Community 85 - "ReceiptStatus"
Cohesion: 0.29
Nodes (3): ReceiptStatus, Complete, Partial

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
Nodes (5): {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 200 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 206 - "App\Services\Inventory\BatchAllocator"
Cohesion: 0.40
Nodes (3): BatchAllocator, {closure#1}(), {closure#2}()

## Knowledge Gaps
- **100 isolated node(s):** `resendVerificationNotification`, `partials.settings-heading`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+95 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 689 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **163 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User Model & Auth` to `InventoryBatches`, `ProfileValidationRules`, `InventoryItems`, `Database Seeders`, `ReportsTest.php`, `Requisitions`, `SecurityTest.php`, `Breadcrumbs Header`, `AuthenticationTest.php`, `StockMonitoringTest.php`, `InventoryAdjustmentsFactory`, `EmailVerificationTest.php`, `TransactionHistoryTest.php`, `PasswordResetTest.php`, `UserFactory.php`, `ProfileValidationRules.php`, `.apply`, `ReceiptStatus`, `ExpirationMonitoringTest.php`, `Livewire\Livewire`?**
  _High betweenness centrality (0.045) - this node is a cross-community bridge._
- **What connects `resendVerificationNotification`, `partials.settings-heading`, `settings.delete-user-form` to the rest of the system?**
  _100 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App\Livewire\Monitoring\StockMonitoring` be split into smaller, more focused modules?**
  _Cohesion score 0.09666666666666666 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `InventoryBatches` to `App\Livewire\Monitoring\StockMonitoring`, `App\Livewire\Monitoring\Reports`, `InventoryItems`, `Livewire\Form`, `Stocks Browser`, `Inventory Adjustment Form`, `ReportsTest.php`, `Illuminate\Database\Eloquent\Builder`, `Requisitions`, `DashboardTest.php`, `ReceivingCreateForm`, `StockMonitoringTest.php`, `InventoryAdjustmentsFactory`, `TransactionHistoryTest.php`, `InventoryBatchesFactory.php`, `App\Services\Inventory\BatchAllocator`, `.apply`, `Illuminate\Database\Eloquent\Model`, `App\Livewire\Monitoring\ExpirationMonitoring`, `ExpirationMonitoringTest.php`, `Illuminate\Support\Carbon`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `Security` connect `Security Settings Component` to `ProfileValidationRules`, `Livewire\Component`, `Profile`, `SecurityTest.php`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.12923076923076923 - nodes in this community are weakly interconnected._