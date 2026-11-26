# Graph Report - inventory-system-prototype  (2026-11-26)

## Corpus Check
- 322 files · ~98,556 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 18, .css 2, .example 1)

## Summary
- 2266 nodes · 4480 edges · 261 communities (75 shown, 186 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 112 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `a40e3359`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ApprovalsTest.php
- Livewire\WithPagination
- Frontend NPM Dependencies
- Inventory/StockReconciliation.php
- AdjustmentType
- Issuances
- StockStatus
- InventoryAdjustmentsForm
- StockMonitoringTest.php
- StockReconciliation
- StockStatusAlertsTest.php
- Illuminate\Support\Collection
- Reports Aggregation
- static
- Illuminate\Database\Eloquent\Builder
- PartialDeliveryTest.php
- Illuminate\Database\Schema\Blueprint
- StockReconciliationTest.php
- InventoryCategories
- App\Models\InventoryItems
- Stocks
- DeliveryTimelineTest.php
- StockMonitoring
- SuppliersTest.php
- StockReconciliation
- Attachments.php
- ExpirationMonitoring
- Suppliers
- Security
- RequisitionStatus
- User
- chart.js
- PasswordValidationRules
- DashboardTest.php
- App\Models\StorageLocations
- Dashboard
- Composer Scripts
- Requisitions.php
- App\Models\StockReceipts
- StockReconciliation
- Composer Metadata
- Composer Dev Dependencies
- ExpirationMonitoringTest.php
- Deploy Workflow
- ReceivingTest.php
- Security Blade View
- HeaderTest.php
- Approvals
- RequestsTest.php
- Illuminate\Contracts\View\View
- ReportsTest.php
- TransactionHistoryTest.php
- receiving.blade.php
- App\Models\InventoryBatches
- AdjustmentsTest.php
- Composer Runtime Deps
- TrackRequestTest.php
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Items
- requests.blade.php
- RequisitionsForm
- ProfileUpdateTest.php
- Composer Config
- InventoryItems
- receiveRemaining
- approvals.blade
- composer
- composer (2)
- Illuminate\Database\Eloquent\Relations\HasMany
- table
- table (2)
- stock-monitoring.blade
- .view
- expiration-monitoring.blade
- StockStatusMonitor
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
- IssuancesTest.php
- BatchCodeGenerator
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- App\Models\Approvals
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- partials.head
- card.blade
- simple.blade
- split.blade
- App\Models\StorageLocations
- request-form.blade.php
- storage-locations.blade.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- categories.blade
- appearance.blade
- SecurityTest.php
- Requisitions
- Livewire\Form
- RequisitionStatusUpdated
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- App\Services\Inventory\StockStatusMonitor
- public-portal.blade.php
- RequestEmailNotificationsTest.php
- guest.blade.php
- ExpirationMonitoring.php
- TrackRequest
- DonationReceivingTest.php
- MailTemplatesTest.php
- App\Models\InventoryCategories
- App\Enums\AdjustmentType
- Receiving.php
- InventoryBatches
- RecoveryCodes
- Pest.php
- monitoring/stock-reconciliation.blade.php
- App\Support\MailLogo
- 2026_11_16_071730_create_suppliers_table.php
- ReceiptStatus
- Logout.php

## God Nodes (most connected - your core abstractions)
1. `dtRoot()` - 37 edges
2. `AdjustmentType` - 35 edges
3. `InventoryItems` - 34 edges
4. `RequisitionStatus` - 29 edges
5. `StockReceipts` - 29 edges
6. `User` - 28 edges
7. `InventoryTransactionType` - 28 edges
8. `Stocks` - 26 edges
9. `dtFollowUp()` - 26 edges
10. `Reports` - 25 edges

## Surprising Connections (you probably didn't know these)
- `ledger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/ReportsTest.php → app/Models/InventoryTransactions.php
- `stockLedger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/StockMonitoringTest.php → app/Models/InventoryTransactions.php
- `{closure#2}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#10}()` --calls--> `RequisitionReceived`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionReceived.php

## Import Cycles
- None detected.

## Communities (261 total, 186 thin omitted)

### Community 0 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 1 - "Livewire\WithPagination"
Cohesion: 0.08
Nodes (3): StorageLocations, Adjustments, Categories

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "Inventory/StockReconciliation.php"
Cohesion: 0.10
Nodes (11): {closure#1}(), {closure#10}(), {closure#14}(), {closure#16}(), {closure#18}(), {closure#20}(), {closure#21}(), {closure#22}() (+3 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.13
Nodes (12): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#1}(), {closure#2}() (+4 more)

### Community 6 - "StockStatus"
Cohesion: 0.18
Nodes (5): StockStatus, Critical, Healthy, Out, Reorder

### Community 7 - "InventoryAdjustmentsForm"
Cohesion: 0.17
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 8 - "StockMonitoringTest.php"
Cohesion: 0.29
Nodes (16): {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+8 more)

### Community 9 - "StockReconciliation"
Cohesion: 0.15
Nodes (3): {closure#8}(), {closure#9}(), TransactionHistory

### Community 10 - "StockStatusAlertsTest.php"
Cohesion: 0.24
Nodes (14): alertBaseline(), alertBatch(), alertIssue(), alertItem(), alertReceive(), alertsSentTo(), {closure#11}(), {closure#12}() (+6 more)

### Community 11 - "Illuminate\Support\Collection"
Cohesion: 0.11
Nodes (8): AdministrativeAreaService, {closure#1}(), {closure#11}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}()

### Community 14 - "static"
Cohesion: 0.16
Nodes (3): InventoryAdjustmentsFactory, StockReceiptsFactory, UserFactory

### Community 15 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.08
Nodes (29): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+21 more)

### Community 17 - "PartialDeliveryTest.php"
Cohesion: 0.09
Nodes (40): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+32 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.15
Nodes (10): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+2 more)

### Community 19 - "StockReconciliationTest.php"
Cohesion: 0.15
Nodes (32): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+24 more)

### Community 20 - "InventoryCategories"
Cohesion: 0.14
Nodes (5): InventoryItemsForm, ItemCreateForm, ItemEditForm, AuditLogs, Appearance

### Community 22 - "App\Models\InventoryItems"
Cohesion: 0.10
Nodes (19): {closure#11}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+11 more)

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.09
Nodes (39): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+31 more)

### Community 26 - "SuppliersTest.php"
Cohesion: 0.06
Nodes (8): SuppliersForm, SupplierForm, Suppliers, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 33 - "RequisitionStatus"
Cohesion: 0.16
Nodes (20): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#10}() (+12 more)

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "PasswordValidationRules"
Cohesion: 0.17
Nodes (5): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, DeleteUserForm

### Community 37 - "DashboardTest.php"
Cohesion: 0.14
Nodes (29): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#19}() (+21 more)

### Community 39 - "Dashboard"
Cohesion: 0.09
Nodes (7): Dashboard, {closure#10}(), {closure#12}(), {closure#25}(), {closure#6}(), {closure#8}(), {closure#12}()

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Requisitions.php"
Cohesion: 0.19
Nodes (4): {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 42 - "App\Models\StockReceipts"
Cohesion: 0.06
Nodes (8): {closure#3}(), StockReceiptsForm, {closure#10}(), {closure#4}(), ReceivingCreateForm, StockReceipts, DeliveryProgress, InventoryBatchesSeeder

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 46 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 49 - "ReceivingTest.php"
Cohesion: 0.21
Nodes (5): {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), receivingForm()

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "HeaderTest.php"
Cohesion: 0.15
Nodes (4): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}()

### Community 53 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 55 - "ReportsTest.php"
Cohesion: 0.13
Nodes (16): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#8}(), {closure#4}(), {closure#10}(), {closure#2}() (+8 more)

### Community 56 - "TransactionHistoryTest.php"
Cohesion: 0.31
Nodes (6): {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyIds()

### Community 57 - "receiving.blade.php"
Cohesion: 0.33
Nodes (5): closeDelivery, confirmClose, livewire.inventory.partials.receipt-details, receiveRemaining, view

### Community 58 - "App\Models\InventoryBatches"
Cohesion: 0.09
Nodes (16): {closure#2}(), {closure#2}(), {closure#7}(), {closure#9}(), {closure#22}(), {closure#23}(), {closure#26}(), {closure#1}() (+8 more)

### Community 59 - "AdjustmentsTest.php"
Cohesion: 0.23
Nodes (7): adjust(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#9}()

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux (+2 more)

### Community 65 - "requests.blade.php"
Cohesion: 0.50
Nodes (3): cancel, livewire.requisitions.partials.requester-details, view

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "InventoryItems"
Cohesion: 0.15
Nodes (7): {closure#1}(), {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator, {closure#1}()

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 82 - "stock-monitoring.blade"
Cohesion: 0.40
Nodes (4): livewire.monitoring.partials.stock-detail, clearFilters, filterStatus, toggle

### Community 83 - ".view"
Cohesion: 0.05
Nodes (19): InventoryCategoriesForm, CategoryCreateForm, CategoryMultiSelect, AppServiceProvider, {closure#1}(), {closure#1}(), {closure#10}(), {closure#2}() (+11 more)

### Community 84 - "expiration-monitoring.blade"
Cohesion: 0.50
Nodes (3): confirmWriteOff, clearFilters, filterStatus

### Community 85 - "StockStatusMonitor"
Cohesion: 0.24
Nodes (3): CheckStockStatus, {closure#2}(), StockStatusMonitor

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

### Community 106 - "IssuancesTest.php"
Cohesion: 0.06
Nodes (24): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, InventoryItemsFactory, StorageLocationsFactory, SuppliersFactory (+16 more)

### Community 109 - "issuances.blade"
Cohesion: 0.40
Nodes (4): confirmRelease, open, release, livewire.requisitions.partials.requester-details

### Community 124 - "App\Models\StorageLocations"
Cohesion: 0.11
Nodes (7): InventoryAdjustments, StockIssuances, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), TransactionReferences

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.22
Nodes (7): configurations.storage-location-form, delete, editViewing, inventory.supplier-form, partials.configuration-heading, sort, toggleActive

### Community 127 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.09
Nodes (7): {closure#7}(), AuditLogs, InventoryTransactions, RequisitionItems, StockIssuanceBatches, StockIssuanceItems, StockReceiptLines

### Community 198 - "Requisitions"
Cohesion: 0.12
Nodes (7): ApprovalDecision, Approved, Rejected, {closure#2}(), {closure#3}(), {closure#1}(), Requisitions

### Community 199 - "Livewire\Form"
Cohesion: 0.20
Nodes (3): InventoryBatchesForm, {closure#2}(), InventoryTransactionsForm

### Community 200 - "RequisitionStatusUpdated"
Cohesion: 0.19
Nodes (5): RequisitionStatusUpdated, StockStatusAlert, {closure#15}(), {closure#4}(), {closure#7}()

### Community 208 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 211 - "ExpirationMonitoring.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 218 - "DonationReceivingTest.php"
Cohesion: 0.12
Nodes (10): {closure#10}(), {closure#11}(), {closure#12}(), {closure#19}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}() (+2 more)

### Community 219 - "MailTemplatesTest.php"
Cohesion: 0.18
Nodes (9): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#9}() (+1 more)

### Community 224 - "App\Enums\AdjustmentType"
Cohesion: 0.22
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsSeeder

### Community 242 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.25
Nodes (6): clearBatch, clearFilters, clearReceipt, exportPdf, showExceptions, toggleSortDirection

### Community 260 - "ReceiptStatus"
Cohesion: 0.33
Nodes (4): ReceiptStatus, Closed, Complete, Partial

## Knowledge Gaps
- **107 isolated node(s):** `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+102 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 894 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **186 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `PasswordResetTest.php`, `Illuminate\Support\Str`, `PasswordValidationRules`, `ProfileUpdateTest.php`, `Requisitions`, `App\Services\Inventory\StockStatusMonitor`, `EmailVerificationTest.php`, `SecurityTest.php`, `App\Models\InventoryCategories`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **What connects `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form` to the rest of the system?**
  _107 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Livewire\WithPagination` be split into smaller, more focused modules?**
  _Cohesion score 0.07671957671957672 - nodes in this community are weakly interconnected._
- **Why does `StockMonitoring` connect `StockMonitoring` to `Livewire\WithPagination`, `Receiving.php`, `App\Models\StockReceipts`, `InventoryCategories`, `App\Models\InventoryItems`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._
- **Why does `TransactionHistory` connect `StockReconciliation` to `Livewire\WithPagination`, `Receiving.php`, `App\Models\StockReceipts`, `Illuminate\Database\Eloquent\Builder`, `InventoryCategories`, `TransactionHistoryTest.php`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._
- **Should `Inventory/StockReconciliation.php` be split into smaller, more focused modules?**
  _Cohesion score 0.09523809523809523 - nodes in this community are weakly interconnected._