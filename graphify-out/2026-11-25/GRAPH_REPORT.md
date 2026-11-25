# Graph Report - inventory-system-prototype  (2026-11-25)

## Corpus Check
- 303 files · ~91,694 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 2, .example 1)

## Summary
- 2178 nodes · 4225 edges · 251 communities (75 shown, 176 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 104 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `dbedfd59`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- StockReconciliationTest.php
- App\Livewire\Monitoring\StockMonitoring
- Frontend NPM Dependencies
- DashboardTest.php
- AdjustmentType
- Illuminate\Database\Eloquent\Factories\Factory
- StockStatus
- InventoryAdjustmentsForm
- ReceivingCreateForm
- StockReconciliation
- Illuminate\Support\Carbon
- Illuminate\Support\Collection
- InventoryTransactionType
- Reports Aggregation
- InventoryAdjustmentsFactory
- Security
- App\Services\AdministrativeAreas\AdministrativeAreaService
- PartialDeliveryTest.php
- Illuminate\Database\Schema\Blueprint
- FortifyServiceProvider.php
- InventoryCategories
- App\Services\Inventory\DeliveryProgress
- Stocks
- DeliveryTimelineTest.php
- ApprovalsTest.php
- StockReceipts.php
- ExpirationMonitoring
- bootstrap/app.php
- App\Livewire\Inventory\Adjustments
- PasswordValidationRules
- App\Models\InventoryBatches
- StockReceipts
- User
- chart.js
- Illuminate\Database\Eloquent\Builder
- App\Models\InventoryItems
- DonationReceivingTest.php
- Dashboard
- Composer Scripts
- Suppliers
- RequisitionStatusUpdated
- Composer Metadata
- Composer Dev Dependencies
- StorageLocationForm
- Deploy Workflow
- Receiving
- Livewire\Attributes\Computed
- Security Blade View
- HeaderTest.php
- StockStatusAlertsTest.php
- RequestsTest.php
- App\Models\InventoryTransactions
- Livewire\Component
- TransactionHistoryTest.php
- DashboardTest.php
- Receiving.php
- StockReconciliation
- Composer Runtime Deps
- InventoryItems
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Items
- MailTemplatesTest.php
- RequisitionsForm
- ReceivingTest.php
- pdPartialForm
- Composer Config
- StockMonitoringTest.php
- AppServiceProvider.php
- App\Models\Requisitions
- approvals.blade
- composer
- composer (2)
- App\Models\StockIssuances
- table
- table (2)
- Illuminate\Support\Str
- stock-monitoring.blade
- StockMonitoring
- expiration-monitoring.blade
- TrackRequestTest.php
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
- StorageLocationsTest.php
- TrackRequest
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- Requisitions
- Suppliers
- RecoveryCodes
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
- Illuminate\Database\Eloquent\Relations\HasMany
- appearance.blade
- 2026_11_16_081138_create_storage_locations_table.php
- 2026_11_16_081232_create_inventory_batches_table.php
- public-portal.blade.php
- Approvals
- guest.blade.php
- Illuminate\Database\Eloquent\Builder
- 2026_11_16_081117_create_stock_receipt_lines_table.php
- IssuancesTest.php
- App\Models\Approvals
- StockStatusMonitor
- App\Livewire\Inventory\Stocks
- Requests
- Livewire\WithPagination
- InventoryCategoriesForm
- App\Enums\AdjustmentType
- Pest.php
- InventoryAdjustmentsFactory.php
- App\Support\MailLogo
- Logout.php
- monitoring/stock-reconciliation.blade.php
- ReceivingCreateForm
- Illuminate\Database\Eloquent\Model
- FortifyServiceProvider

## God Nodes (most connected - your core abstractions)
1. `StockReceipts` - 43 edges
2. `InventoryItems` - 42 edges
3. `AdjustmentType` - 39 edges
4. `InventoryBatches` - 35 edges
5. `dtRoot()` - 35 edges
6. `User` - 33 edges
7. `InventoryTransactionType` - 29 edges
8. `RequisitionStatus` - 28 edges
9. `StockReconciliation` - 25 edges
10. `pdRoot()` - 25 edges

## Surprising Connections (you probably didn't know these)
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [INFERRED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php

## Import Cycles
- None detected.

## Communities (251 total, 176 thin omitted)

### Community 0 - "StockReconciliationTest.php"
Cohesion: 0.10
Nodes (36): {closure#2}(), {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+28 more)

### Community 1 - "App\Livewire\Monitoring\StockMonitoring"
Cohesion: 0.14
Nodes (5): {closure#1}(), {closure#2}(), {closure#26}(), {closure#3}(), {closure#9}()

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "DashboardTest.php"
Cohesion: 0.17
Nodes (19): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, assertStatusMailSent() (+11 more)

### Community 4 - "AdjustmentType"
Cohesion: 0.13
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, adjust(), {closure#12}() (+8 more)

### Community 5 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.06
Nodes (11): StorageLocations, BatchCodeGenerator, {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, {closure#1}() (+3 more)

### Community 6 - "StockStatus"
Cohesion: 0.18
Nodes (5): StockStatus, Critical, Healthy, Out, Reorder

### Community 7 - "InventoryAdjustmentsForm"
Cohesion: 0.18
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 9 - "StockReconciliation"
Cohesion: 0.07
Nodes (4): StockReconciliation, {closure#8}(), {closure#9}(), TransactionHistory

### Community 11 - "Illuminate\Support\Collection"
Cohesion: 0.17
Nodes (8): {closure#10}(), {closure#12}(), {closure#25}(), {closure#6}(), {closure#8}(), AdministrativeAreaService, {closure#4}(), {closure#7}()

### Community 12 - "InventoryTransactionType"
Cohesion: 0.15
Nodes (14): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#4}(), {closure#10}(), {closure#2}(), {closure#4}() (+6 more)

### Community 14 - "InventoryAdjustmentsFactory"
Cohesion: 0.17
Nodes (3): InventoryAdjustmentsFactory, StockReceiptsFactory, UserFactory

### Community 16 - "App\Services\AdministrativeAreas\AdministrativeAreaService"
Cohesion: 0.12
Nodes (4): {closure#11}(), {closure#2}(), {closure#5}(), {closure#8}()

### Community 17 - "PartialDeliveryTest.php"
Cohesion: 0.11
Nodes (26): {closure#19}(), {closure#20}(), {closure#22}(), {closure#23}(), {closure#24}(), {closure#25}(), {closure#26}(), {closure#27}() (+18 more)

### Community 18 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.23
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 19 - "FortifyServiceProvider.php"
Cohesion: 0.21
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 20 - "InventoryCategories"
Cohesion: 0.15
Nodes (4): {closure#2}(), InventoryItemsForm, ItemCreateForm, ItemEditForm

### Community 22 - "App\Services\Inventory\DeliveryProgress"
Cohesion: 0.12
Nodes (6): ReceiptStatus, Closed, Complete, Partial, {closure#1}(), {closure#2}()

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.11
Nodes (37): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+29 more)

### Community 25 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 28 - "ExpirationMonitoring"
Cohesion: 0.12
Nodes (12): {closure#5}(), ExpirationMonitoring, {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}() (+4 more)

### Community 29 - "bootstrap/app.php"
Cohesion: 0.23
Nodes (6): {closure#10}(), {closure#8}(), {closure#9}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 31 - "PasswordValidationRules"
Cohesion: 0.14
Nodes (5): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, DeleteUserForm

### Community 32 - "App\Models\InventoryBatches"
Cohesion: 0.25
Nodes (8): {closure#1}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 35 - "chart.js"
Cohesion: 0.11
Nodes (19): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+11 more)

### Community 36 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.10
Nodes (10): {closure#10}(), {closure#11}(), {closure#13}(), {closure#15}(), {closure#17}(), {closure#19}(), {closure#21}(), {closure#22}() (+2 more)

### Community 37 - "App\Models\InventoryItems"
Cohesion: 0.10
Nodes (15): {closure#11}(), {closure#13}(), {closure#14}(), {closure#5}(), {closure#7}(), {closure#3}(), {closure#4}(), {closure#5}() (+7 more)

### Community 38 - "DonationReceivingTest.php"
Cohesion: 0.13
Nodes (8): {closure#16}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}(), donationForm()

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Suppliers"
Cohesion: 0.06
Nodes (8): SuppliersForm, SupplierForm, Suppliers, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 43 - "RequisitionStatusUpdated"
Cohesion: 0.19
Nodes (5): RequisitionStatusUpdated, StockStatusAlert, {closure#15}(), {closure#4}(), {closure#7}()

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

### Community 52 - "StockStatusAlertsTest.php"
Cohesion: 0.24
Nodes (14): alertBaseline(), alertBatch(), alertIssue(), alertItem(), alertReceive(), alertsSentTo(), {closure#11}(), {closure#12}() (+6 more)

### Community 53 - "RequestsTest.php"
Cohesion: 0.12
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 55 - "Livewire\Component"
Cohesion: 0.15
Nodes (3): AuditLogs, Appearance, Profile

### Community 56 - "TransactionHistoryTest.php"
Cohesion: 0.26
Nodes (8): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyEntry(), historyIds()

### Community 57 - "DashboardTest.php"
Cohesion: 0.21
Nodes (17): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#15}(), {closure#4}(), {closure#5}(), {closure#6}() (+9 more)

### Community 59 - "StockReconciliation"
Cohesion: 0.27
Nodes (4): {closure#1}(), {closure#23}(), {closure#8}(), StockReconciliation

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux (+2 more)

### Community 61 - "InventoryItems"
Cohesion: 0.14
Nodes (9): {closure#1}(), {closure#1}(), {closure#2}(), InventoryItems, {closure#1}(), BatchAllocator, {closure#2}(), {closure#4}() (+1 more)

### Community 65 - "MailTemplatesTest.php"
Cohesion: 0.18
Nodes (9): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#9}() (+1 more)

### Community 68 - "ReceivingTest.php"
Cohesion: 0.20
Nodes (5): {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), receivingForm()

### Community 69 - "pdPartialForm"
Cohesion: 0.23
Nodes (14): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+6 more)

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "StockMonitoringTest.php"
Cohesion: 0.31
Nodes (12): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+4 more)

### Community 73 - "App\Models\Requisitions"
Cohesion: 0.17
Nodes (4): {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}()

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

### Community 112 - "Requisitions"
Cohesion: 0.12
Nodes (7): ApprovalDecision, Approved, Rejected, {closure#2}(), {closure#3}(), {closure#1}(), Requisitions

### Community 115 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 124 - "InventoryBatches"
Cohesion: 0.20
Nodes (4): InventoryBatchesForm, {closure#22}(), {closure#23}(), InventoryBatches

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): addItem, edit, removeItem, startOver, submit, track

### Community 126 - "storage-locations.blade.php"
Cohesion: 0.12
Nodes (13): cancel, closeDelivery, configurations.storage-location-form, confirmClose, delete, editViewing, inventory.supplier-form, partials.configuration-heading (+5 more)

### Community 127 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.09
Nodes (4): InventoryTransactionsForm, InventoryTransactions, RequisitionItems, StockReceiptLines

### Community 211 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.18
Nodes (14): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#1}(), {closure#2}(), {closure#3}() (+6 more)

### Community 218 - "IssuancesTest.php"
Cohesion: 0.10
Nodes (18): IssuancesForm, Issuances, approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}() (+10 more)

### Community 238 - "InventoryAdjustmentsFactory.php"
Cohesion: 0.38
Nodes (3): {closure#1}(), {closure#2}(), {closure#4}()

### Community 242 - "monitoring/stock-reconciliation.blade.php"
Cohesion: 0.25
Nodes (6): clearBatch, clearFilters, clearReceipt, exportPdf, showExceptions, toggleSortDirection

### Community 248 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.23
Nodes (3): Attachments, AuditLogs, StockIssuanceBatches

## Knowledge Gaps
- **103 isolated node(s):** `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+98 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 883 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **176 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `App\Livewire\Inventory\Stocks`, `EmailVerificationTest.php`, `Laravel\Fortify\Features`, `StockMonitoringTest.php`, `ProfileUpdateTest.php`, `InventoryTransactionType`, `Requisitions`, `Illuminate\Support\Str`, `SecurityTest.php`, `TransactionHistoryTest.php`, `PasswordResetTest.php`, `ExpirationMonitoring`, `InventoryItems`, `PasswordValidationRules`?**
  _High betweenness centrality (0.051) - this node is a cross-community bridge._
- **What connects `partials.settings-heading`, `resendVerificationNotification`, `settings.delete-user-form` to the rest of the system?**
  _103 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `StockReconciliationTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.0988235294117647 - nodes in this community are weakly interconnected._
- **Why does `Security` connect `Security` to `SecurityTest.php`, `Livewire\Component`, `PasswordValidationRules`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **Should `App\Livewire\Monitoring\StockMonitoring` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `InventoryItems` to `Illuminate\Database\Eloquent\Relations\HasMany`, `App\Livewire\Inventory\Stocks`, `App\Models\InventoryItems`, `Illuminate\Database\Eloquent\Factories\Factory`, `StockMonitoringTest.php`, `Suppliers`, `RequisitionStatusUpdated`, `InventoryTransactionType`, `Illuminate\Database\Eloquent\Builder`, `InventoryCategories`, `StockMonitoring`, `App\Models\InventoryTransactions`, `Illuminate\Database\Eloquent\Model`, `TransactionHistoryTest.php`, `ExpirationMonitoring`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._