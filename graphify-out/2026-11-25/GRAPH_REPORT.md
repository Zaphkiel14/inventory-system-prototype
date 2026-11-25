# Graph Report - inventory-system-prototype  (2026-11-25)

## Corpus Check
- 305 files · ~92,464 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 2, .example 1)

## Summary
- 2194 nodes · 4249 edges · 243 communities (73 shown, 170 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 103 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `8796423e`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- StockReconciliationTest.php
- Livewire\Component
- Frontend NPM Dependencies
- App\Models\Requisitions
- AdjustmentType
- InventoryBatchesFactory.php
- StockStatus
- InventoryAdjustmentsForm
- ReceivingCreateForm
- StockReconciliation
- RequisitionStatusUpdated
- Illuminate\Support\Collection
- InventoryTransactionType
- Reports Aggregation
- InventoryAdjustmentsFactory
- MailTemplatesTest.php
- PartialDeliveryTest.php
- Illuminate\Database\Schema\Blueprint
- App\Models\InventoryBatches
- InventoryCategories
- App\Models\InventoryItems
- Stocks
- DeliveryTimelineTest.php
- Livewire\Form
- StockReceipts.php
- ExpirationMonitoring
- FortifyServiceProvider.php
- App\Livewire\Inventory\Adjustments
- Security
- InventoryAdjustmentsSeeder.php
- Receiving.php
- User
- chart.js
- Illuminate\Database\Eloquent\Builder
- StockMetrics
- App\Models\Requisitions
- Dashboard
- Composer Scripts
- Suppliers
- StockStatusAlertsTest.php
- Composer Metadata
- Composer Dev Dependencies
- StorageLocationForm
- Deploy Workflow
- Receiving
- Livewire\Attributes\Computed
- Security Blade View
- HeaderTest.php
- Illuminate\Database\Seeder
- RequestsTest.php
- .request
- Livewire\Component
- TransactionHistoryTest.php
- receiving.blade.php
- StockReconciliation
- StockReconciliation
- Composer Runtime Deps
- InventoryItems
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Items
- requests.blade.php
- RequisitionsForm
- App\Models\StorageLocations
- App\Support\MailLogo
- Composer Config
- StockMonitoringTest.php
- receiveRemaining
- approvals.blade
- composer
- composer (2)
- App\Models\StockIssuances
- table
- table (2)
- stock-monitoring.blade
- StockMonitoring
- expiration-monitoring.blade
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
- TrackRequest
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- ApprovalsTest.php
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
- InventoryBatches
- request-form.blade.php
- storage-locations.blade.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- categories.blade
- StockReceipts
- appearance.blade
- Requisitions
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- public-portal.blade.php
- guest.blade.php
- App\Livewire\Monitoring\ExpirationMonitoring
- 2026_11_16_081117_create_stock_receipt_lines_table.php
- IssuancesTest.php
- Livewire\WithPagination
- InventoryCategoriesForm
- App\Enums\AdjustmentType
- Pest.php
- App\Models\Approvals
- monitoring/stock-reconciliation.blade.php
- App\Models\Suppliers
- ExpirationMonitoringTest.php
- BatchCodeGenerator
- ReceiptStatus

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 42 edges
2. `AdjustmentType` - 39 edges
3. `dtRoot()` - 37 edges
4. `StockReceipts` - 36 edges
5. `InventoryBatches` - 35 edges
6. `User` - 33 edges
7. `InventoryTransactionType` - 29 edges
8. `RequisitionStatus` - 28 edges
9. `dtFollowUp()` - 26 edges
10. `pdRoot()` - 25 edges

## Surprising Connections (you probably didn't know these)
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php
- `{closure#11}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (243 total, 170 thin omitted)

### Community 0 - "StockReconciliationTest.php"
Cohesion: 0.15
Nodes (32): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+24 more)

### Community 1 - "Livewire\Component"
Cohesion: 0.14
Nodes (3): AuditLogs, PublicPortal, Appearance

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "App\Models\Requisitions"
Cohesion: 0.09
Nodes (36): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#8}() (+28 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.13
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, adjust(), {closure#12}() (+8 more)

### Community 5 - "InventoryBatchesFactory.php"
Cohesion: 0.15
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory

### Community 6 - "StockStatus"
Cohesion: 0.09
Nodes (10): CheckStockStatus, StockStatus, Critical, Healthy, Out, Reorder, {closure#2}(), {closure#4}() (+2 more)

### Community 7 - "InventoryAdjustmentsForm"
Cohesion: 0.17
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 9 - "StockReconciliation"
Cohesion: 0.13
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 10 - "RequisitionStatusUpdated"
Cohesion: 0.19
Nodes (5): RequisitionStatusUpdated, StockStatusAlert, {closure#15}(), {closure#4}(), {closure#7}()

### Community 11 - "Illuminate\Support\Collection"
Cohesion: 0.13
Nodes (7): AdministrativeAreaService, {closure#11}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 12 - "InventoryTransactionType"
Cohesion: 0.15
Nodes (14): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#10}(), {closure#2}(), {closure#4}() (+6 more)

### Community 14 - "InventoryAdjustmentsFactory"
Cohesion: 0.17
Nodes (3): InventoryAdjustmentsFactory, StockReceiptsFactory, UserFactory

### Community 15 - "MailTemplatesTest.php"
Cohesion: 0.20
Nodes (9): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#9}() (+1 more)

### Community 17 - "PartialDeliveryTest.php"
Cohesion: 0.05
Nodes (49): {closure#10}(), {closure#17}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}() (+41 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "App\Models\InventoryBatches"
Cohesion: 0.09
Nodes (5): {closure#2}(), {closure#1}(), dashLedger(), rcAdjust(), rcLedger()

### Community 20 - "InventoryCategories"
Cohesion: 0.13
Nodes (5): InventoryItemsForm, ItemCreateForm, ItemEditForm, InventoryCategories, {closure#1}()

### Community 22 - "App\Models\InventoryItems"
Cohesion: 0.07
Nodes (24): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#25}(), {closure#26}() (+16 more)

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.09
Nodes (39): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+31 more)

### Community 25 - "Livewire\Form"
Cohesion: 0.19
Nodes (3): InventoryBatchesForm, {closure#2}(), InventoryTransactionsForm

### Community 27 - "StockReceipts.php"
Cohesion: 0.22
Nodes (4): {closure#10}(), {closure#4}(), DeliveryProgress, InventoryBatchesSeeder

### Community 29 - "FortifyServiceProvider.php"
Cohesion: 0.06
Nodes (17): Requests, AppServiceProvider, {closure#1}(), {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}() (+9 more)

### Community 31 - "Security"
Cohesion: 0.06
Nodes (8): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, Logout, DeleteUserForm, Security, RecoveryCodes

### Community 32 - "InventoryAdjustmentsSeeder.php"
Cohesion: 0.22
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.08
Nodes (22): {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+14 more)

### Community 38 - "App\Models\Requisitions"
Cohesion: 0.19
Nodes (4): {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Suppliers"
Cohesion: 0.10
Nodes (3): SuppliersForm, SupplierForm, Suppliers

### Community 43 - "StockStatusAlertsTest.php"
Cohesion: 0.12
Nodes (19): {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), receivingForm(), alertBaseline(), alertBatch(), alertIssue() (+11 more)

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

### Community 51 - "HeaderTest.php"
Cohesion: 0.15
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 52 - "Illuminate\Database\Seeder"
Cohesion: 0.12
Nodes (6): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 56 - "TransactionHistoryTest.php"
Cohesion: 0.16
Nodes (9): InventoryTransactions, {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyEntry() (+1 more)

### Community 57 - "receiving.blade.php"
Cohesion: 0.33
Nodes (5): closeDelivery, confirmClose, livewire.inventory.partials.receipt-details, receiveRemaining, view

### Community 59 - "StockReconciliation"
Cohesion: 0.27
Nodes (4): {closure#1}(), {closure#23}(), {closure#8}(), StockReconciliation

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux (+2 more)

### Community 61 - "InventoryItems"
Cohesion: 0.18
Nodes (5): {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 78 - "App\Models\StockIssuances"
Cohesion: 0.11
Nodes (7): InventoryAdjustments, StockIssuances, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), TransactionReferences

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

### Community 112 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 115 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 124 - "InventoryBatches"
Cohesion: 0.20
Nodes (3): {closure#22}(), {closure#23}(), InventoryBatches

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.22
Nodes (7): configurations.storage-location-form, delete, editViewing, inventory.supplier-form, partials.configuration-heading, sort, toggleActive

### Community 127 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.10
Nodes (6): Attachments, AuditLogs, RequisitionItems, StockIssuanceBatches, StockIssuanceItems, StockReceiptLines

### Community 198 - "Requisitions"
Cohesion: 0.06
Nodes (10): ApprovalDecision, Approved, Rejected, ApprovalsForm, {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}() (+2 more)

### Community 211 - "App\Livewire\Monitoring\ExpirationMonitoring"
Cohesion: 0.15
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}()

### Community 218 - "IssuancesTest.php"
Cohesion: 0.10
Nodes (18): IssuancesForm, Issuances, approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}() (+10 more)

### Community 235 - "App\Enums\AdjustmentType"
Cohesion: 0.18
Nodes (5): {closure#6}(), {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsSeeder

### Community 242 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.25
Nodes (6): clearBatch, clearFilters, clearReceipt, exportPdf, showExceptions, toggleSortDirection

### Community 251 - "App\Models\Suppliers"
Cohesion: 0.14
Nodes (4): InventoryCategoriesFactory, InventoryItemsFactory, StorageLocationsFactory, SuppliersFactory

### Community 253 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 257 - "ReceiptStatus"
Cohesion: 0.33
Nodes (4): ReceiptStatus, Closed, Complete, Partial

## Knowledge Gaps
- **106 isolated node(s):** `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+101 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 892 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **170 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Support\Str`, `App\Models\StorageLocations`, `StockStatus`, `Requisitions`, `Laravel\Fortify\Features`, `StockMonitoringTest.php`, `ProfileUpdateTest.php`, `InventoryTransactionType`, `EmailVerificationTest.php`, `Illuminate\Database\Seeder`, `SecurityTest.php`, `Livewire\Component`, `TransactionHistoryTest.php`, `PasswordResetTest.php`, `ExpirationMonitoringTest.php`, `Security`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **What connects `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form` to the rest of the system?**
  _106 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Livewire\Component` be split into smaller, more focused modules?**
  _Cohesion score 0.1380952380952381 - nodes in this community are weakly interconnected._
- **Why does `ReceivingCreateForm` connect `ReceivingCreateForm` to `Livewire\Component`, `Suppliers`, `Suppliers`, `App\Models\InventoryBatches`, `App\Livewire\Inventory\ReceivingCreateForm`, `StockReceipts.php`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `InventoryBatches` to `App\Models\Requisitions`, `App\Models\StorageLocations`, `Illuminate\Database\Eloquent\Builder`, `InventoryBatchesFactory.php`, `InventoryAdjustmentsForm`, `StockMonitoringTest.php`, `InventoryTransactionType`, `App\Models\InventoryBatches`, `App\Livewire\Monitoring\ExpirationMonitoring`, `App\Models\InventoryItems`, `TransactionHistoryTest.php`, `Livewire\Form`, `ExpirationMonitoringTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **Should `App\Models\Requisitions` be split into smaller, more focused modules?**
  _Cohesion score 0.09268707482993198 - nodes in this community are weakly interconnected._