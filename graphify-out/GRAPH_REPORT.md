# Graph Report - inventory-system-prototype  (2026-11-25)

## Corpus Check
- 293 files · ~79,649 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 2, .example 1)

## Summary
- 1939 nodes · 3651 edges · 248 communities (67 shown, 181 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 90 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `7bad24aa`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- StockReconciliationTest.php
- SuppliersForm
- Frontend NPM Dependencies
- StatusNotificationsTest.php
- AdjustmentType
- Livewire\Attributes\On
- Issuances
- InventoryAdjustmentsForm
- .view
- StockReconciliation
- StockStatus
- Stock Levels & Admin Areas
- InventoryTransactionType
- Reports Aggregation
- PasswordValidationRules
- Security
- .request
- App\Models\InventoryItems
- Illuminate\Database\Schema\Blueprint
- ReceivingCreateForm
- InventoryItemsForm
- StockStatusAlertsTest.php
- Stocks
- ExpirationMonitoringTest.php
- ApprovalsTest.php
- MailTemplatesTest.php
- Illuminate\Support\Carbon
- StockStatusAlert
- App\Models\StorageLocations
- App\Livewire\Inventory\Adjustments
- App\Models\Requisitions
- InventoryAdjustmentsFactory
- User
- chart.js
- Inventory/StockReconciliation.php
- Illuminate\Database\Eloquent\Factories\Factory
- Dashboard
- Composer Scripts
- Livewire\WithPagination
- HeaderTest.php
- Composer Metadata
- Composer Dev Dependencies
- StockMonitoringTest.php
- Deploy Workflow
- Category Multi-Select
- Security Blade View
- InventoryBatches
- InventoryItems
- RequestsTest.php
- Requisitions
- TransactionHistoryTest.php
- App\Models\StockIssuances
- StockReconciliation
- Composer Runtime Deps
- Illuminate\Database\Seeder
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Livewire\Component
- InventoryTransactionType
- App\Livewire\Inventory\Suppliers
- IssuancesTest.php
- Composer Config
- App\Livewire\Forms\ApprovalsForm
- StockReceipts
- RequisitionsForm
- approvals.blade
- composer
- composer (2)
- ExpirationMonitoringTest.php
- table
- table (2)
- Illuminate\Database\Eloquent\Relations\BelongsTo
- stock-monitoring.blade
- ReceivingTest.php
- expiration-monitoring.blade
- StockMonitoring
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
- Approvals
- requests.blade.php
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- stocks.blade
- RequestForm
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- partials.head
- card.blade
- simple.blade
- split.blade
- App\Livewire\Inventory\Receiving
- request-form.blade.php
- storage-locations.blade.php
- App\Models\InventoryAdjustments
- categories.blade
- items.blade
- appearance.blade
- App\Services\AdministrativeAreas\AdministrativeAreaService
- Illuminate\Database\Eloquent\Relations\HasMany
- static
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- Profile
- public-portal.blade.php
- .apply
- guest.blade.php
- App\Models\InventoryTransactions
- Attachments.php
- Illuminate\Support\Carbon
- RequestEmailNotificationsTest.php
- App\Concerns\ProfileValidationRules
- ApprovalsForm
- App\Models\Approvals
- Pest.php
- StockReceiptsSeeder.php
- App\Models\Suppliers
- App\Models\InventoryCategories
- RecoveryCodes
- App\Support\MailLogo
- Logout.php
- TrackRequest
- InventoryTransactions
- monitoring/stock-reconciliation.blade.php

## God Nodes (most connected - your core abstractions)
1. `InventoryBatches` - 50 edges
2. `InventoryItems` - 48 edges
3. `AdjustmentType` - 44 edges
4. `User` - 37 edges
5. `InventoryTransactionType` - 32 edges
6. `StockReceipts` - 30 edges
7. `RequisitionStatus` - 28 edges
8. `Requisitions` - 28 edges
9. `StockReconciliation` - 23 edges
10. `Reports` - 23 edges

## Surprising Connections (you probably didn't know these)
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#11}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#5}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#6}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#9}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (248 total, 181 thin omitted)

### Community 0 - "StockReconciliationTest.php"
Cohesion: 0.17
Nodes (29): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+21 more)

### Community 1 - "SuppliersForm"
Cohesion: 0.17
Nodes (3): SuppliersForm, SupplierForm, Suppliers

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "StatusNotificationsTest.php"
Cohesion: 0.09
Nodes (37): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#8}() (+29 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.13
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#6}(), adjust() (+8 more)

### Community 7 - "InventoryAdjustmentsForm"
Cohesion: 0.13
Nodes (5): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}(), InventoryAdjustments

### Community 8 - ".view"
Cohesion: 0.09
Nodes (14): Requests, {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+6 more)

### Community 9 - "StockReconciliation"
Cohesion: 0.07
Nodes (4): StockReconciliation, {closure#8}(), {closure#9}(), TransactionHistory

### Community 10 - "StockStatus"
Cohesion: 0.06
Nodes (13): CheckStockStatus, StockStatus, Critical, Healthy, Out, Reorder, AppServiceProvider, {closure#1}() (+5 more)

### Community 12 - "InventoryTransactionType"
Cohesion: 0.27
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 14 - "PasswordValidationRules"
Cohesion: 0.21
Nodes (4): CreateNewUser, ResetUserPassword, PasswordValidationRules, DeleteUserForm

### Community 17 - "App\Models\InventoryItems"
Cohesion: 0.06
Nodes (24): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#2}(), {closure#25}() (+16 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "ReceivingCreateForm"
Cohesion: 0.14
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 20 - "InventoryItemsForm"
Cohesion: 0.17
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 22 - "StockStatusAlertsTest.php"
Cohesion: 0.24
Nodes (14): alertBaseline(), alertBatch(), alertIssue(), alertItem(), alertReceive(), alertsSentTo(), {closure#11}(), {closure#12}() (+6 more)

### Community 25 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 26 - "MailTemplatesTest.php"
Cohesion: 0.16
Nodes (10): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+2 more)

### Community 29 - "App\Models\StorageLocations"
Cohesion: 0.09
Nodes (7): StorageLocationForm, InventoryBatchesForm, InventoryCategoriesForm, {closure#2}(), InventoryTransactionsForm, StorageLocationsForm, CategoryCreateForm

### Community 32 - "App\Models\Requisitions"
Cohesion: 0.24
Nodes (5): {closure#1}(), {closure#1}(), {closure#2}(), RequisitionStatusUpdated, {closure#7}()

### Community 33 - "InventoryAdjustmentsFactory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "Inventory/StockReconciliation.php"
Cohesion: 0.08
Nodes (22): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#1}(), {closure#2}(), {closure#3}() (+14 more)

### Community 37 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.15
Nodes (4): InventoryCategoriesFactory, InventoryItemsFactory, StorageLocationsFactory, SuppliersFactory

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

### Community 46 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "InventoryBatches"
Cohesion: 0.16
Nodes (10): {closure#22}(), {closure#23}(), InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}() (+2 more)

### Community 52 - "InventoryItems"
Cohesion: 0.15
Nodes (7): {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator, {closure#1}(), {closure#2}()

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 56 - "TransactionHistoryTest.php"
Cohesion: 0.24
Nodes (7): StorageLocations, {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

### Community 57 - "App\Models\StockIssuances"
Cohesion: 0.19
Nodes (4): StockIssuances, {closure#1}(), {closure#3}(), TransactionReferences

### Community 59 - "StockReconciliation"
Cohesion: 0.27
Nodes (4): {closure#1}(), {closure#23}(), {closure#8}(), StockReconciliation

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux (+2 more)

### Community 61 - "Illuminate\Database\Seeder"
Cohesion: 0.15
Nodes (5): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 65 - "Livewire\Component"
Cohesion: 0.14
Nodes (3): AuditLogs, PublicPortal, Appearance

### Community 67 - "InventoryTransactionType"
Cohesion: 0.22
Nodes (7): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#1}(), historyEntry()

### Community 69 - "IssuancesTest.php"
Cohesion: 0.22
Nodes (14): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+6 more)

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "App\Livewire\Forms\ApprovalsForm"
Cohesion: 0.24
Nodes (6): ApprovalDecision, Approved, Rejected, {closure#1}(), {closure#2}(), {closure#3}()

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 78 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 82 - "stock-monitoring.blade"
Cohesion: 0.40
Nodes (4): livewire.monitoring.partials.stock-detail, clearFilters, filterStatus, toggle

### Community 83 - "ReceivingTest.php"
Cohesion: 0.39
Nodes (4): {closure#3}(), {closure#4}(), {closure#5}(), receivingForm()

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

### Community 211 - "App\Models\InventoryTransactions"
Cohesion: 0.12
Nodes (9): {closure#2}(), {closure#1}(), {closure#26}(), {closure#7}(), {closure#1}(), {closure#9}(), rcAdjust(), rcIssue() (+1 more)

### Community 218 - "Illuminate\Support\Carbon"
Cohesion: 0.15
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory

### Community 222 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 235 - "App\Models\Suppliers"
Cohesion: 0.25
Nodes (3): ReceiptStatus, Complete, Partial

## Knowledge Gaps
- **98 isolated node(s):** `Critical`, `Healthy`, `Out`, `Reorder`, `partials.settings-heading` (+93 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 792 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **181 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `InventoryTransactionType`, `PasswordValidationRules`, `InventoryAdjustmentsFactory`, `EmailVerificationTest.php`, `SecurityTest.php`, `StockMonitoringTest.php`, `PasswordResetTest.php`, `InventoryBatches`, `Requisitions`, `TransactionHistoryTest.php`, `Illuminate\Database\Seeder`, `InventoryTransactionType`, `ExpirationMonitoringTest.php`, `.apply`, `App\Concerns\ProfileValidationRules`, `ProfileUpdateTest.php`, `StockReceiptsSeeder.php`, `AuthenticationTest.php`, `App\Services\Inventory\StockStatusMonitor`?**
  _High betweenness centrality (0.041) - this node is a cross-community bridge._
- **What connects `Critical`, `Healthy`, `Out` to the rest of the system?**
  _98 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `AdjustmentType` connect `AdjustmentType` to `InventoryAdjustmentsFactory`, `Inventory/StockReconciliation.php`, `InventoryAdjustmentsForm`, `InventoryTransactionType`, `ExpirationMonitoringTest.php`, `.apply`, `InventoryBatches`, `TransactionHistoryTest.php`, `Livewire\Attributes\Computed`, `App\Livewire\Inventory\Adjustments`, `App\Models\InventoryAdjustments`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **Should `StatusNotificationsTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.09224489795918367 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `Livewire\Attributes\On`, `StockStatus`, `InventoryTransactionType`, `App\Models\InventoryItems`, `InventoryItemsForm`, `Illuminate\Support\Carbon`, `StockStatusAlert`, `App\Models\StorageLocations`, `User`, `Inventory/StockReconciliation.php`, `StockMonitoringTest.php`, `TransactionHistoryTest.php`, `Illuminate\Database\Seeder`, `App\Livewire\Forms\ApprovalsForm`, `Illuminate\Database\Eloquent\Relations\HasMany`, `ExpirationMonitoringTest.php`, `Attachments.php`, `Illuminate\Support\Carbon`, `App\Models\InventoryCategories`, `App\Services\Inventory\StockStatusMonitor`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.12923076923076923 - nodes in this community are weakly interconnected._