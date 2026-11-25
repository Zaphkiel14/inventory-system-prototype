# Graph Report - inventory-system-prototype  (2026-11-25)

## Corpus Check
- 295 files · ~82,992 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 2, .example 1)

## Summary
- 1994 nodes · 3750 edges · 238 communities (69 shown, 169 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 87 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `1ebdddea`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- StockReconciliationTest.php
- StockStatusAlertsTest.php
- Frontend NPM Dependencies
- StatusNotificationsTest.php
- AdjustmentType
- Livewire\WithPagination
- StockReconciliation
- InventoryAdjustmentsForm
- FortifyServiceProvider.php
- StockReconciliation
- StockStatus
- Illuminate\Support\Collection
- InventoryTransactionType
- Reports Aggregation
- InventoryAdjustmentsFactory
- Security
- App\Services\AdministrativeAreas\AdministrativeAreaService
- AppServiceProvider.php
- Illuminate\Database\Schema\Blueprint
- ReceivingTest.php
- InventoryItemsForm
- Illuminate\Database\Eloquent\Builder
- Stocks
- InventoryBatchesFactory.php
- ApprovalsTest.php
- ExpirationMonitoring
- MailTemplatesTest.php
- bootstrap/app.php
- App\Livewire\Inventory\Adjustments
- App\Livewire\Monitoring\StockMonitoring
- App\Enums\AdjustmentType
- App\Services\Inventory\StockStatusMonitor
- User
- chart.js
- Inventory/StockReconciliation.php
- Issuances
- Dashboard
- Composer Scripts
- App\Livewire\Configurations\StorageLocations
- HeaderTest.php
- Composer Metadata
- Composer Dev Dependencies
- App\Models\InventoryItems
- Deploy Workflow
- Receiving.php
- Livewire\Attributes\Computed
- Security Blade View
- InventoryBatches
- InventoryItems
- RequestsTest.php
- App\Models\InventoryBatches
- TransactionHistoryTest.php
- BatchCodeGenerator
- StockReconciliation
- Composer Runtime Deps
- Illuminate\Database\Seeder
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Items
- Profile
- RequestForm
- Requests
- Suppliers
- Composer Config
- StockMonitoringTest.php
- FortifyServiceProvider
- StockStatusAlert
- approvals.blade
- composer
- composer (2)
- ExpirationMonitoringTest.php
- table
- table (2)
- stock-monitoring.blade
- StockMonitoring
- expiration-monitoring.blade
- RequisitionsForm
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
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- Illuminate\Database\Eloquent\Factories\Factory
- Suppliers
- RequestEmailNotificationsTest.php
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- partials.head
- card.blade
- simple.blade
- split.blade
- request-form.blade.php
- storage-locations.blade.php
- Illuminate\Database\Eloquent\Model
- categories.blade
- SuppliersForm
- appearance.blade
- SupplierForm
- StockStatusAlert
- StockReceipts
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- DatabaseSeeder.php
- public-portal.blade.php
- InventoryAdjustments
- guest.blade.php
- Requisitions
- BatchAllocator
- IssuancesTest.php
- App\Support\MailLogo
- TrackRequest
- Pest.php
- InventoryCategoriesForm
- Livewire\Component
- monitoring/stock-reconciliation.blade.php

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 47 edges
2. `AdjustmentType` - 39 edges
3. `InventoryBatches` - 38 edges
4. `User` - 34 edges
5. `InventoryTransactionType` - 30 edges
6. `RequisitionStatus` - 28 edges
7. `StockReceipts` - 26 edges
8. `StockReconciliation` - 25 edges
9. `Requisitions` - 24 edges
10. `Reports` - 23 edges

## Surprising Connections (you probably didn't know these)
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#11}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#5}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#6}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (238 total, 169 thin omitted)

### Community 0 - "StockReconciliationTest.php"
Cohesion: 0.05
Nodes (40): {closure#2}(), InventoryTransactionsForm, {closure#1}(), {closure#1}(), StockMetrics, dashLedger(), {closure#10}(), {closure#11}() (+32 more)

### Community 1 - "StockStatusAlertsTest.php"
Cohesion: 0.24
Nodes (14): alertBaseline(), alertBatch(), alertIssue(), alertItem(), alertReceive(), alertsSentTo(), {closure#11}(), {closure#12}() (+6 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "StatusNotificationsTest.php"
Cohesion: 0.09
Nodes (36): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#8}() (+28 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.13
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, adjust(), {closure#12}() (+8 more)

### Community 7 - "InventoryAdjustmentsForm"
Cohesion: 0.17
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 8 - "FortifyServiceProvider.php"
Cohesion: 0.26
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 9 - "StockReconciliation"
Cohesion: 0.13
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 10 - "StockStatus"
Cohesion: 0.13
Nodes (8): StockStatus, Critical, Healthy, Out, Reorder, {closure#5}(), {closure#4}(), {closure#5}()

### Community 12 - "InventoryTransactionType"
Cohesion: 0.15
Nodes (14): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#10}(), {closure#2}(), {closure#4}() (+6 more)

### Community 14 - "InventoryAdjustmentsFactory"
Cohesion: 0.18
Nodes (3): InventoryAdjustmentsFactory, StockReceiptsFactory, UserFactory

### Community 15 - "Security"
Cohesion: 0.06
Nodes (8): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, Logout, DeleteUserForm, Security, RecoveryCodes

### Community 16 - "App\Services\AdministrativeAreas\AdministrativeAreaService"
Cohesion: 0.10
Nodes (6): {closure#11}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "ReceivingTest.php"
Cohesion: 0.05
Nodes (10): StorageLocationForm, {closure#1}(), StockReceiptsForm, StorageLocationsForm, ReceivingCreateForm, {closure#3}(), {closure#4}(), {closure#5}() (+2 more)

### Community 20 - "InventoryItemsForm"
Cohesion: 0.16
Nodes (3): InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 22 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.14
Nodes (17): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#1}(), {closure#2}(), {closure#3}() (+9 more)

### Community 24 - "InventoryBatchesFactory.php"
Cohesion: 0.15
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory

### Community 25 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 28 - "MailTemplatesTest.php"
Cohesion: 0.16
Nodes (10): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}() (+2 more)

### Community 29 - "bootstrap/app.php"
Cohesion: 0.23
Nodes (6): {closure#10}(), {closure#8}(), {closure#9}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 31 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.11
Nodes (9): {closure#1}(), {closure#10}(), {closure#12}(), {closure#2}(), {closure#25}(), {closure#26}(), {closure#3}(), {closure#6}() (+1 more)

### Community 32 - "App\Enums\AdjustmentType"
Cohesion: 0.18
Nodes (5): {closure#6}(), {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsSeeder

### Community 33 - "App\Services\Inventory\StockStatusMonitor"
Cohesion: 0.24
Nodes (3): CheckStockStatus, {closure#2}(), StockStatusMonitor

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "Inventory/StockReconciliation.php"
Cohesion: 0.10
Nodes (11): {closure#10}(), {closure#11}(), {closure#13}(), {closure#15}(), {closure#17}(), {closure#19}(), {closure#21}(), {closure#22}() (+3 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 43 - "HeaderTest.php"
Cohesion: 0.13
Nodes (5): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}(), {closure#4}()

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 46 - "App\Models\InventoryItems"
Cohesion: 0.16
Nodes (11): {closure#11}(), {closure#13}(), {closure#14}(), {closure#7}(), {closure#9}(), {closure#3}(), {closure#1}(), {closure#2}() (+3 more)

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "InventoryBatches"
Cohesion: 0.15
Nodes (4): InventoryBatchesForm, {closure#22}(), {closure#23}(), InventoryBatches

### Community 52 - "InventoryItems"
Cohesion: 0.10
Nodes (11): {closure#1}(), {closure#2}(), InventoryCategories, {closure#1}(), {closure#2}(), InventoryItems, StorageLocations, {closure#1}() (+3 more)

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 54 - "App\Models\InventoryBatches"
Cohesion: 0.22
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 56 - "TransactionHistoryTest.php"
Cohesion: 0.16
Nodes (9): InventoryTransactions, {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyEntry() (+1 more)

### Community 59 - "StockReconciliation"
Cohesion: 0.27
Nodes (4): {closure#1}(), {closure#23}(), {closure#8}(), StockReconciliation

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux (+2 more)

### Community 61 - "Illuminate\Database\Seeder"
Cohesion: 0.21
Nodes (4): InventoryCategoriesSeeder, InventoryItemsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 73 - "StockStatusAlert"
Cohesion: 0.23
Nodes (4): {closure#1}(), {closure#1}(), {closure#2}(), RequisitionStatusUpdated

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

### Community 109 - "issuances.blade"
Cohesion: 0.40
Nodes (4): confirmRelease, livewire.requisitions.partials.requester-details, open, release

### Community 112 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.14
Nodes (4): InventoryCategoriesFactory, InventoryItemsFactory, StorageLocationsFactory, SuppliersFactory

### Community 115 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.14
Nodes (10): cancel, configurations.storage-location-form, delete, editViewing, inventory.supplier-form, partials.configuration-heading, livewire.requisitions.partials.requester-details, sort (+2 more)

### Community 127 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (6): Approvals, Attachments, AuditLogs, RequisitionItems, StockIssuanceBatches, StockIssuanceItems

### Community 200 - "StockReceipts"
Cohesion: 0.15
Nodes (5): ReceiptStatus, Complete, Partial, StockReceipts, {closure#2}()

### Community 208 - "InventoryAdjustments"
Cohesion: 0.11
Nodes (6): InventoryAdjustments, StockIssuances, {closure#1}(), {closure#3}(), {closure#4}(), TransactionReferences

### Community 211 - "Requisitions"
Cohesion: 0.06
Nodes (9): ApprovalDecision, Approved, Rejected, ApprovalsForm, {closure#2}(), {closure#3}(), {closure#1}(), Approvals (+1 more)

### Community 218 - "IssuancesTest.php"
Cohesion: 0.21
Nodes (16): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+8 more)

### Community 238 - "Livewire\Component"
Cohesion: 0.14
Nodes (3): AuditLogs, PublicPortal, Appearance

### Community 242 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.25
Nodes (6): clearBatch, clearFilters, clearReceipt, exportPdf, showExceptions, toggleSortDirection

## Knowledge Gaps
- **99 isolated node(s):** `Critical`, `Healthy`, `Out`, `Reorder`, `partials.settings-heading` (+94 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 830 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **169 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Profile`, `Suppliers`, `EmailVerificationTest.php`, `Livewire\Attributes\Computed`, `StockMonitoringTest.php`, `StockStatus`, `PasswordResetTest.php`, `InventoryTransactionType`, `SecurityTest.php`, `DatabaseSeeder.php`, `Security`, `ExpirationMonitoringTest.php`, `PasswordResetTest.php`, `Illuminate\Support\Str`, `InventoryItems`, `ProfileUpdateTest.php`, `TransactionHistoryTest.php`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **What connects `Critical`, `Healthy`, `Out` to the rest of the system?**
  _99 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `StockReconciliationTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.05479818230419674 - nodes in this community are weakly interconnected._
- **Why does `Issuances` connect `Issuances` to `Livewire\WithPagination`, `Livewire\Attributes\Computed`, `Livewire\Component`, `Requisitions`, `IssuancesTest.php`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `AdjustmentType` connect `AdjustmentType` to `App\Enums\AdjustmentType`, `InventoryAdjustmentsForm`, `InventoryTransactionType`, `InventoryAdjustmentsFactory`, `ExpirationMonitoringTest.php`, `App\Models\InventoryBatches`, `Illuminate\Database\Eloquent\Builder`, `TransactionHistoryTest.php`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Should `StatusNotificationsTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.09268707482993198 - nodes in this community are weakly interconnected._