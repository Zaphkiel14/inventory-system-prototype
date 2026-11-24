# Graph Report - inventory-system-prototype  (2026-11-24)

## Corpus Check
- 286 files · ~71,793 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 2, .example 1)

## Summary
- 1816 nodes · 3363 edges · 228 communities (62 shown, 166 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 89 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `82a64677`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- App\Livewire\Monitoring\StockMonitoring
- DashboardTest.php
- Frontend NPM Dependencies
- DashboardTest.php
- AdjustmentType
- Livewire\WithPagination
- Issuances
- IssuancesTest.php
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
- ExpirationMonitoring
- ApprovalsTest.php
- AppServiceProvider.php
- Illuminate\Support\Carbon
- ReportsTest.php
- InventoryItemsForm
- App\Livewire\Inventory\Adjustments
- Illuminate\Database\Eloquent\Builder
- App\Models\StorageLocations
- App\Models\Requisitions
- User
- chart.js
- StockStatus
- Dashboard
- Composer Scripts
- ProfileUpdateTest.php
- HeaderTest.php
- Composer Metadata
- Composer Dev Dependencies
- TransactionHistoryTest.php
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- ExpirationMonitoringTest.php
- InventoryItems
- RequestsTest.php
- InventoryTransactionType
- App\Livewire\Inventory\Receiving
- Requisitions
- App\Models\StockIssuances
- App\Livewire\Requisitions\Requests
- Composer Runtime Deps
- MailTemplatesTest.php
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- InventoryCategoriesForm
- Illuminate\Database\Eloquent\Relations\HasMany
- Suppliers
- Illuminate\Database\Eloquent\Factories\Factory
- Composer Config
- Approvals
- InventoryAdjustments
- RequisitionsForm
- approvals.blade
- composer
- composer (2)
- ReceivingTest.php
- table
- table (2)
- Illuminate\Database\Eloquent\Relations\BelongsTo
- stock-monitoring.blade
- expiration-monitoring.blade
- StockMonitoring
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
- StockReceiptsSeeder.php
- categories.blade
- items.blade
- appearance.blade
- App\Services\AdministrativeAreas\AdministrativeAreaService
- RequestEmailNotificationsTest.php
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- ApprovalsForm
- public-portal.blade.php
- guest.blade.php
- Livewire\Component
- Attachments.php
- MailLogo.php
- TrackRequest
- InventoryTransactions
- .request
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
- `{closure#4}()` --references--> `InventoryBatches`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Models/InventoryBatches.php
- `{closure#7}()` --references--> `InventoryBatches`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Models/InventoryBatches.php
- `{closure#2}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (228 total, 166 thin omitted)

### Community 0 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.08
Nodes (19): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#25}(), {closure#26}(), {closure#6}() (+11 more)

### Community 1 - "DashboardTest.php"
Cohesion: 0.21
Nodes (17): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#15}(), {closure#4}(), {closure#5}(), {closure#6}() (+9 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "DashboardTest.php"
Cohesion: 0.16
Nodes (20): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#8}() (+12 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.06
Nodes (28): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#6}(), {closure#1}() (+20 more)

### Community 7 - "IssuancesTest.php"
Cohesion: 0.22
Nodes (14): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+6 more)

### Community 8 - "bootstrap/app.php"
Cohesion: 0.14
Nodes (13): {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+5 more)

### Community 9 - "Suppliers"
Cohesion: 0.17
Nodes (3): SuppliersForm, SupplierForm, Suppliers

### Community 10 - "StockStatusAlertsTest.php"
Cohesion: 0.24
Nodes (14): alertBaseline(), alertBatch(), alertIssue(), alertItem(), alertReceive(), alertsSentTo(), {closure#11}(), {closure#12}() (+6 more)

### Community 12 - "Transaction History Filters"
Cohesion: 0.13
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 15 - "Security"
Cohesion: 0.06
Nodes (8): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, Logout, DeleteUserForm, Security, RecoveryCodes

### Community 16 - "Livewire\Form"
Cohesion: 0.18
Nodes (3): InventoryBatchesForm, {closure#2}(), InventoryTransactionsForm

### Community 17 - "Illuminate\Database\Seeder"
Cohesion: 0.15
Nodes (5): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 20 - "App\Livewire\Forms\ApprovalsForm"
Cohesion: 0.24
Nodes (6): ApprovalDecision, Approved, Rejected, {closure#1}(), {closure#2}(), {closure#3}()

### Community 24 - "ExpirationMonitoring"
Cohesion: 0.09
Nodes (6): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#5}(), {closure#8}(), ExpirationMonitoring

### Community 25 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 26 - "AppServiceProvider.php"
Cohesion: 0.14
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 28 - "ReportsTest.php"
Cohesion: 0.26
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 29 - "InventoryItemsForm"
Cohesion: 0.19
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 31 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.11
Nodes (21): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#1}(), {closure#2}(), {closure#22}() (+13 more)

### Community 33 - "App\Models\Requisitions"
Cohesion: 0.15
Nodes (7): {closure#1}(), {closure#1}(), {closure#2}(), RequisitionStatusUpdated, StockStatusAlert, {closure#15}(), {closure#4}()

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "StockStatus"
Cohesion: 0.09
Nodes (11): CheckStockStatus, StockStatus, Critical, Healthy, Out, Reorder, {closure#5}(), {closure#2}() (+3 more)

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

### Community 51 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 52 - "InventoryItems"
Cohesion: 0.12
Nodes (8): {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator, {closure#1}(), {closure#1}(), {closure#2}()

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "InventoryTransactionType"
Cohesion: 0.22
Nodes (7): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#1}(), historyEntry()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 61 - "MailTemplatesTest.php"
Cohesion: 0.16
Nodes (10): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}() (+2 more)

### Community 69 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.05
Nodes (13): ReceiptStatus, Complete, Partial, {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory (+5 more)

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 72 - "InventoryAdjustments"
Cohesion: 0.19
Nodes (4): InventoryAdjustments, {closure#1}(), {closure#4}(), TransactionReferences

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

### Community 82 - "stock-monitoring.blade"
Cohesion: 0.40
Nodes (4): livewire.monitoring.partials.stock-detail, clearFilters, filterStatus, toggle

### Community 84 - "expiration-monitoring.blade"
Cohesion: 0.50
Nodes (3): confirmWriteOff, clearFilters, filterStatus

### Community 87 - "StockReceipts"
Cohesion: 0.25
Nodes (3): InventoryCategories, StockReceipts, {closure#2}()

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
Cohesion: 0.10
Nodes (6): {closure#11}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 200 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 211 - "Livewire\Component"
Cohesion: 0.17
Nodes (3): AuditLogs, PublicPortal, Appearance

## Knowledge Gaps
- **96 isolated node(s):** `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+91 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 746 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **166 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `AdjustmentType`, `App\Models\InventoryTransactions`, `Security`, `Illuminate\Database\Seeder`, `StockMonitoringTest.php`, `ReportsTest.php`, `StockStatus`, `EmailVerificationTest.php`, `ProfileUpdateTest.php`, `SecurityTest.php`, `TransactionHistoryTest.php`, `PasswordResetTest.php`, `ExpirationMonitoringTest.php`, `InventoryTransactionType`, `Requisitions`, `PasswordResetTest.php`, `Illuminate\Support\Str`, `StockReceipts`, `StockReceiptsSeeder.php`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **What connects `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form` to the rest of the system?**
  _96 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `App\Livewire\Monitoring\StockMonitoring` be split into smaller, more focused modules?**
  _Cohesion score 0.08021390374331551 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `Illuminate\Database\Eloquent\Builder` to `App\Livewire\Monitoring\StockMonitoring`, `DashboardTest.php`, `AdjustmentType`, `App\Models\InventoryTransactions`, `Livewire\Form`, `StockMonitoringTest.php`, `App\Livewire\Monitoring\Reports`, `Stocks Browser`, `ExpirationMonitoring`, `Illuminate\Support\Carbon`, `ReportsTest.php`, `TransactionHistoryTest.php`, `ExpirationMonitoringTest.php`, `InventoryItems`, `App\Livewire\Requisitions\Issuances`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Database\Eloquent\Factories\Factory`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `StockReceipts`, `Attachments.php`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `AdjustmentType` connect `AdjustmentType` to `App\Livewire\Requisitions\Issuances`, `InventoryAdjustments`, `App\Models\InventoryTransactions`, `TransactionHistoryTest.php`, `ExpirationMonitoringTest.php`, `ExpirationMonitoring`, `ReportsTest.php`, `App\Livewire\Inventory\Adjustments`, `Illuminate\Database\Eloquent\Builder`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.06328320802005012 - nodes in this community are weakly interconnected._