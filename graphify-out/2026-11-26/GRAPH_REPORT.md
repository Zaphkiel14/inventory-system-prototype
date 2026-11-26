# Graph Report - inventory-system-prototype  (2026-11-26)

## Corpus Check
- 342 files · ~112,461 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 24 file(s) not represented in the graph (top: (none) 18, .css 2, .example 1)

## Summary
- 2670 nodes · 5380 edges · 281 communities (95 shown, 186 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 92 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Graph Freshness
- Built from commit: `7857eb52`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ApprovalsTest.php
- Security
- Frontend NPM Dependencies
- Illuminate\Database\Seeder
- AdjustmentType
- AdjustmentCreateForm
- App\Livewire\Forms\StockReceiptsForm
- QueryBudgetTest.php
- Illuminate\Database\Eloquent\Factories\Factory
- StockReconciliationTest.php
- RequisitionStatusUpdated
- AdministrativeAreaService
- InventoryItemsForm
- Reports Aggregation
- Illuminate\Database\Eloquent\Relations\HasMany
- StockReconciliation
- Illuminate\Database\Eloquent\Builder
- PartialDeliveryTest.php
- 2026_11_26_091833_add_reporting_indexes_to_inventory_tables.php
- InventoryTransactions
- Livewire\Component
- TransactionHistory
- InventoryItems
- DeliveryTimelineTest.php
- StockMonitoring
- App\Services\Inventory\StockMetrics
- InventoryBatches
- StockStatusAlertsTest.php
- RequestsTest.php
- StockReceipts
- Approvals
- ReceivingCreateForm
- User
- chart.js
- App\Services\AdministrativeAreas\AdministrativeAreaService
- DashboardTest.php
- ReceivingTest.php
- Dashboard
- Composer Scripts
- ExpirationMonitoring
- ExpirationMonitoringTest.php
- InventoryCache
- Composer Metadata
- Composer Dev Dependencies
- App\Models\InventoryItems
- Deploy Workflow
- Illuminate\Database\Schema\Blueprint
- InventoryBatchesTest.php
- Security Blade View
- HeaderTest.php
- InventoryCacheTest.php
- App\Livewire\Requisitions\Issuances
- InventoryAdjustmentsFactory
- ReportsTest.php
- App\Models\Approvals
- receiving.blade.php
- perfSeed
- Stocks
- Composer Runtime Deps
- FortifyServiceProvider.php
- Illuminate\Database\Migrations\Migration
- 2026_11_16_071731_create_stock_receipts_table.php
- Illuminate\Contracts\View\View
- requests.blade.php
- App\Livewire\Forms\RequisitionsForm
- TelescopeServiceProvider.php
- Receiving
- Composer Config
- web.php
- receipt-details.blade.php
- approvals.blade
- composer
- composer (2)
- DonationReceivingTest.php
- table
- App\Livewire\Inventory\Adjustments
- livewire.monitoring.partials.stock-detail
- expiration-monitoring.blade
- BatchAllocator
- table (4)
- Team Agents
- MailTemplatesTest.php
- Livewire\Form
- table (8)
- Illuminate\Database\Migrations\Migration
- table (10)
- App\Livewire\Inventory\Receiving
- table (12)
- table (13)
- table (14)
- table (15)
- StockMetrics
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
- App\Livewire\Forms\RequisitionsForm
- PublicPortal
- 2026_11_26_143822_create_telescope_entries_table.php
- Controller
- apple-touch-icon
- Robots.txt Crawler Policy (allow all user agents)
- header.blade
- partials.head
- card.blade
- simple.blade
- split.blade
- request-form.blade.php
- inventory/suppliers.blade.php
- Illuminate\Database\Eloquent\Model
- categories.blade
- 2026_11_26_202137_add_code_indexes_to_inventory_batches.php
- appearance.blade
- StorageLocations
- RecoveryCodes
- Attachments.php
- {closure#44}
- Livewire\Livewire
- 2026_11_16_081138_create_storage_locations_table.php
- perfDeferredLoad
- Pest.php
- public-portal.blade.php
- configurations/storage-locations.blade.php
- guest.blade.php
- perfRecorder
- SecurityTest.php
- .ledgerVersion
- CategoryMultiSelect.php
- inventory/items.blade.php
- perfDeferredPlaceholder
- receiving-create-form.blade.php
- 2026_11_16_081318_create_inventory_adjustments_table.php
- placeholders/storage-locations.blade.php
- InventoryAdjustments
- Profile
- 0001_01_01_000000_create_users_table.php
- StockFigures.php
- PasswordValidationRules
- inventory/stocks.blade.php
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- 2026_11_16_081117_create_stock_receipt_lines_table.php
- StorageLocations
- 2026_11_17_083930_inventory_category_item.php
- 0001_01_01_000002_create_jobs_table.php
- Issuances
- App\Support\InventoryCache
- TrackRequestTest.php
- RequestEmailNotificationsTest.php
- .bumpLedger
- 2026_11_16_081104_create_inventory_categories_table.php
- .request
- App\Enums\RequisitionStatus
- perfScaleUp
- Suppliers
- MailLogo
- Illuminate\Support\Facades\DB
- monitoring/stock-monitoring.blade.php

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 93 edges
2. `InventoryBatches` - 61 edges
3. `User` - 58 edges
4. `StockReceipts` - 54 edges
5. `RequisitionStatus` - 50 edges
6. `AdjustmentType` - 50 edges
7. `InventoryTransactionType` - 41 edges
8. `dtRoot()` - 37 edges
9. `Requisitions` - 35 edges
10. `InventoryTransactions` - 34 edges

## Surprising Connections (you probably didn't know these)
- `perfScaleUp()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Performance/QueryBudgetTest.php → app/Models/InventoryTransactions.php
- `{closure#7}()` --calls--> `RequisitionStatusUpdated`  [EXTRACTED]
  tests/Feature/Requisitions/MailTemplatesTest.php → app/Notifications/RequisitionStatusUpdated.php
- `stockLedger()` --calls--> `InventoryTransactions`  [INFERRED]
  tests/Feature/Monitoring/StockMonitoringTest.php → app/Models/InventoryTransactions.php
- `{closure#26}()` --references--> `InventoryItems`  [EXTRACTED]
  tests/Feature/Support/InventoryCacheTest.php → app/Models/InventoryItems.php
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php

## Import Cycles
- None detected.

## Communities (281 total, 186 thin omitted)

### Community 0 - "ApprovalsTest.php"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.07
Nodes (35): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+27 more)

### Community 3 - "Illuminate\Database\Seeder"
Cohesion: 0.15
Nodes (5): InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 4 - "AdjustmentType"
Cohesion: 0.11
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, adjust(), {closure#12}() (+8 more)

### Community 5 - "AdjustmentCreateForm"
Cohesion: 0.14
Nodes (6): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#1}(), {closure#2}(), {closure#3}()

### Community 6 - "App\Livewire\Forms\StockReceiptsForm"
Cohesion: 0.11
Nodes (4): ReceiptStatus, Closed, Complete, Partial

### Community 7 - "QueryBudgetTest.php"
Cohesion: 0.04
Nodes (6): {closure#17}(), {closure#45}(), {closure#56}(), {closure#9}(), perfLoadIsland(), perfStockedItem()

### Community 8 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.06
Nodes (10): {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, InventoryItemsFactory, StockReceiptsFactory, StorageLocationsFactory (+2 more)

### Community 9 - "StockReconciliationTest.php"
Cohesion: 0.06
Nodes (72): InventoryBatchesSeeder, {closure#11}(), {closure#12}(), {closure#13}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}() (+64 more)

### Community 10 - "RequisitionStatusUpdated"
Cohesion: 0.21
Nodes (3): Requisitions, RequisitionStatusUpdated, {closure#1}()

### Community 11 - "AdministrativeAreaService"
Cohesion: 0.17
Nodes (5): {closure#6}(), {closure#8}(), AdministrativeAreaService, {closure#4}(), {closure#6}()

### Community 14 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.09
Nodes (4): {closure#1}(), StockIssuances, {closure#3}(), perfIssuanceLine()

### Community 15 - "StockReconciliation"
Cohesion: 0.21
Nodes (5): {closure#1}(), {closure#22}(), {closure#5}(), {closure#9}(), StockReconciliation

### Community 16 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.09
Nodes (19): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#9}() (+11 more)

### Community 17 - "PartialDeliveryTest.php"
Cohesion: 0.09
Nodes (40): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+32 more)

### Community 18 - "2026_11_26_091833_add_reporting_indexes_to_inventory_tables.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 19 - "InventoryTransactions"
Cohesion: 0.14
Nodes (11): InventoryTransactions, {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), historyEntry() (+3 more)

### Community 20 - "Livewire\Component"
Cohesion: 0.08
Nodes (5): StorageLocationForm, ItemCreateForm, ItemEditForm, AuditLogs, Appearance

### Community 22 - "InventoryItems"
Cohesion: 0.11
Nodes (17): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}() (+9 more)

### Community 24 - "DeliveryTimelineTest.php"
Cohesion: 0.09
Nodes (39): {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}() (+31 more)

### Community 27 - "InventoryBatches"
Cohesion: 0.13
Nodes (10): InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 28 - "StockStatusAlertsTest.php"
Cohesion: 0.06
Nodes (26): CheckStockStatus, StockStatus, Critical, Healthy, Out, Reorder, StockStatusAlert, {closure#2}() (+18 more)

### Community 29 - "RequestsTest.php"
Cohesion: 0.13
Nodes (16): {closure#1}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}(), {closure#17}(), {closure#18}(), {closure#19}() (+8 more)

### Community 31 - "StockReceipts"
Cohesion: 0.19
Nodes (4): {closure#10}(), {closure#4}(), StockReceipts, DeliveryProgress

### Community 33 - "ReceivingCreateForm"
Cohesion: 0.12
Nodes (3): {closure#3}(), StockReceiptsForm, ReceivingCreateForm

### Community 35 - "chart.js"
Cohesion: 0.10
Nodes (20): register(), arcPath(), barPath(), chart(), DATE_KEYS, formatter(), linePath(), num() (+12 more)

### Community 36 - "App\Services\AdministrativeAreas\AdministrativeAreaService"
Cohesion: 0.10
Nodes (8): {closure#10}(), {closure#13}(), {closure#2}(), {closure#3}(), {closure#7}(), {closure#9}(), areasInNewRequest(), {closure#3}()

### Community 37 - "DashboardTest.php"
Cohesion: 0.06
Nodes (58): InventoryTransactionType, Adjustment, Issuance, Receipt, RequisitionStatus, Approved, Cancelled, Issued (+50 more)

### Community 38 - "ReceivingTest.php"
Cohesion: 0.17
Nodes (11): {closure#13}(), {closure#14}(), {closure#15}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), codeSequence() (+3 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "ExpirationMonitoring"
Cohesion: 0.10
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), ExpirationMonitoring

### Community 42 - "ExpirationMonitoringTest.php"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 43 - "InventoryCache"
Cohesion: 0.16
Nodes (5): InventoryCache, {closure#13}(), {closure#15}(), {closure#16}(), {closure#5}()

### Community 44 - "Composer Metadata"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 45 - "Composer Dev Dependencies"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 46 - "App\Models\InventoryItems"
Cohesion: 0.13
Nodes (15): {closure#1}(), {closure#2}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#1}(), {closure#2}(), {closure#3}() (+7 more)

### Community 47 - "Deploy Workflow"
Cohesion: 0.17
Nodes (11): runs-on, steps, jobs, deploy, name, on, push, branches (+3 more)

### Community 48 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.17
Nodes (18): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}(), {closure#16}() (+10 more)

### Community 49 - "InventoryBatchesTest.php"
Cohesion: 0.18
Nodes (6): batchExpiringIn(), {closure#12}(), {closure#2}(), {closure#5}(), {closure#8}(), scopeIncludes()

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 51 - "HeaderTest.php"
Cohesion: 0.17
Nodes (5): Breadcrumbs, {closure#2}(), {closure#3}(), {closure#4}(), {closure#4}()

### Community 52 - "InventoryCacheTest.php"
Cohesion: 0.05
Nodes (17): cacheIsland(), cacheObserve(), cacheOpenRelease(), cacheQuantities(), cacheRelease(), cacheStock(), {closure#1}(), {closure#10}() (+9 more)

### Community 53 - "App\Livewire\Requisitions\Issuances"
Cohesion: 0.20
Nodes (5): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}()

### Community 54 - "InventoryAdjustmentsFactory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

### Community 55 - "ReportsTest.php"
Cohesion: 0.27
Nodes (9): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), ledger() (+1 more)

### Community 57 - "receiving.blade.php"
Cohesion: 0.33
Nodes (5): closeDelivery, confirmClose, livewire.inventory.partials.receipt-details, receiveRemaining, view

### Community 58 - "perfSeed"
Cohesion: 0.26
Nodes (14): {closure#15}(), {closure#18}(), {closure#20}(), {closure#22}(), {closure#24}(), {closure#26}(), {closure#29}(), {closure#31}() (+6 more)

### Community 59 - "Stocks"
Cohesion: 0.05
Nodes (4): {closure#10}(), Stocks, {closure#8}(), TransactionHistory

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.18
Nodes (11): require, barryvdh/laravel-dompdf, laravel/chisel, laravel/fortify, laravel/framework, laravel/telescope, laravel/tinker, livewire/blaze (+3 more)

### Community 61 - "FortifyServiceProvider.php"
Cohesion: 0.06
Nodes (12): AppServiceProvider, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#10}(), {closure#8}(), {closure#9}() (+4 more)

### Community 62 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

### Community 65 - "requests.blade.php"
Cohesion: 0.50
Nodes (3): cancel, livewire.requisitions.partials.requester-details, view

### Community 68 - "TelescopeServiceProvider.php"
Cohesion: 0.24
Nodes (3): {closure#1}(), {closure#2}(), TelescopeServiceProvider

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "web.php"
Cohesion: 0.08
Nodes (10): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#1}(), {closure#2}(), {closure#3}() (+2 more)

### Community 74 - "approvals.blade"
Cohesion: 0.33
Nodes (5): approve, fillMaximum, reject, livewire.requisitions.partials.requester-details, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 78 - "DonationReceivingTest.php"
Cohesion: 0.05
Nodes (13): SuppliersForm, SupplierForm, Suppliers, {closure#10}(), {closure#11}(), {closure#12}(), {closure#19}(), {closure#3}() (+5 more)

### Community 84 - "expiration-monitoring.blade"
Cohesion: 0.50
Nodes (3): confirmWriteOff, clearFilters, filterStatus

### Community 87 - "Team Agents"
Cohesion: 0.10
Nodes (19): `.claude/agents/coder.md`, `.claude/agents/designer.md`, `.claude/agents/orchestrator.md`, `.claude/agents/tester.md`, Codebases, Driving the team, Enable it, How We Use Claude (+11 more)

### Community 88 - "MailTemplatesTest.php"
Cohesion: 0.19
Nodes (10): RequisitionReceived, {closure#10}(), {closure#11}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}() (+2 more)

### Community 89 - "Livewire\Form"
Cohesion: 0.15
Nodes (4): InventoryBatchesForm, InventoryCategoriesForm, InventoryTransactionsForm, CategoryCreateForm

### Community 91 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

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

### Community 106 - "IssuancesTest.php"
Cohesion: 0.09
Nodes (30): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+22 more)

### Community 109 - "issuances.blade"
Cohesion: 0.40
Nodes (4): confirmRelease, open, release, livewire.requisitions.partials.requester-details

### Community 115 - "2026_11_26_143822_create_telescope_entries_table.php"
Cohesion: 0.38
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), down(), getConnection(), up()

### Community 125 - "request-form.blade.php"
Cohesion: 0.29
Nodes (6): edit, addItem, removeItem, startOver, submit, track

### Community 126 - "inventory/suppliers.blade.php"
Cohesion: 0.40
Nodes (4): delete, inventory.supplier-form, sort, toggleActive

### Community 127 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (5): AuditLogs, RequisitionItems, StockIssuanceBatches, StockIssuanceItems, StockReceiptLines

### Community 199 - "{closure#44}"
Cohesion: 0.38
Nodes (7): {closure#44}(), {closure#52}(), perfItemWithMostBatches(), perfMeasure(), perfMeasureColdWarm(), perfPrintVolume(), perfUseFileCache()

### Community 205 - "perfDeferredLoad"
Cohesion: 0.67
Nodes (3): {closure#27}(), {closure#60}(), perfDeferredLoad()

### Community 208 - "configurations/storage-locations.blade.php"
Cohesion: 0.40
Nodes (4): configurations.storage-location-form, delete, partials.configuration-heading, sort

### Community 210 - "perfRecorder"
Cohesion: 0.40
Nodes (3): {closure#2}(), {closure#3}(), perfRecorder()

### Community 216 - ".ledgerVersion"
Cohesion: 0.22
Nodes (7): {closure#25}(), {closure#30}(), {closure#35}(), {closure#40}(), {closure#42}(), {closure#47}(), ledgerVersionWrites()

### Community 219 - "inventory/items.blade.php"
Cohesion: 0.50
Nodes (3): editViewing, sort, view

### Community 232 - "InventoryAdjustments"
Cohesion: 0.18
Nodes (6): {closure#8}(), InventoryAdjustments, {closure#1}(), {closure#2}(), {closure#4}(), TransactionReferences

### Community 236 - "0001_01_01_000000_create_users_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 238 - "StockFigures.php"
Cohesion: 0.15
Nodes (8): {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#8}(), {closure#9}()

### Community 239 - "PasswordValidationRules"
Cohesion: 0.15
Nodes (5): CreateNewUser, ResetUserPassword, PasswordValidationRules, Logout, DeleteUserForm

### Community 242 - "inventory/stocks.blade.php"
Cohesion: 0.40
Nodes (4): clearBatch, clearReceipt, clearFilters, toggleSortDirection

### Community 260 - "0001_01_01_000002_create_jobs_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 267 - "Issuances"
Cohesion: 0.12
Nodes (5): IssuancesForm, Issuances, {closure#1}(), {closure#2}(), {closure#7}()

### Community 269 - "App\Support\InventoryCache"
Cohesion: 0.12
Nodes (8): {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#2}(), {closure#3}()

### Community 271 - "RequestEmailNotificationsTest.php"
Cohesion: 0.33
Nodes (7): {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#8}(), submitWithEmail()

### Community 272 - ".bumpLedger"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#1}(), {closure#1}(), {closure#41}()

### Community 278 - "App\Enums\RequisitionStatus"
Cohesion: 0.23
Nodes (6): ApprovalDecision, Approved, Rejected, {closure#2}(), {closure#3}(), {closure#6}()

### Community 279 - "perfScaleUp"
Cohesion: 0.67
Nodes (3): {closure#4}(), perfExpiry(), perfScaleUp()

### Community 290 - "monitoring/stock-monitoring.blade.php"
Cohesion: 0.29
Nodes (5): clearFilters, exportPdf, filterStatus, monitoring.stock-detail, showExceptions

## Knowledge Gaps
- **118 isolated node(s):** `resendVerificationNotification`, `partials.settings-heading`, `settings.delete-user-form`, `gotoPage`, `nextPage` (+113 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1043 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **186 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `InventoryItems` connect `InventoryItems` to `ApprovalsTest.php`, `Illuminate\Database\Seeder`, `AdjustmentType`, `App\Livewire\Forms\StockReceiptsForm`, `Illuminate\Database\Eloquent\Factories\Factory`, `RequisitionStatusUpdated`, `InventoryItemsForm`, `App\Support\InventoryCache`, `Illuminate\Database\Eloquent\Relations\HasMany`, `RequestEmailNotificationsTest.php`, `TrackRequestTest.php`, `PartialDeliveryTest.php`, `InventoryTransactions`, `Livewire\Component`, `DeliveryTimelineTest.php`, `StockStatusAlertsTest.php`, `RequestsTest.php`, `Illuminate\Support\Facades\DB`, `User`, `DashboardTest.php`, `ReceivingTest.php`, `ExpirationMonitoringTest.php`, `App\Models\InventoryItems`, `InventoryCacheTest.php`, `App\Livewire\Requisitions\Issuances`, `ReportsTest.php`, `RecoveryCodes`, `Illuminate\Contracts\View\View`, `web.php`, `Livewire\Livewire`, `DonationReceivingTest.php`, `MailTemplatesTest.php`, `App\Livewire\Inventory\Receiving`, `IssuancesTest.php`, `StockFigures.php`, `App\Livewire\Forms\RequisitionsForm`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.074) - this node is a cross-community bridge._
- **What connects `resendVerificationNotification`, `partials.settings-heading`, `settings.delete-user-form` to the rest of the system?**
  _118 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Security` be split into smaller, more focused modules?**
  _Cohesion score 0.14761904761904762 - nodes in this community are weakly interconnected._
- **Why does `User` connect `User` to `ApprovalsTest.php`, `Illuminate\Database\Seeder`, `AdjustmentType`, `Illuminate\Database\Eloquent\Factories\Factory`, `RequisitionStatusUpdated`, `TrackRequestTest.php`, `Illuminate\Database\Eloquent\Builder`, `PartialDeliveryTest.php`, `InventoryTransactions`, `DeliveryTimelineTest.php`, `InventoryBatches`, `StockStatusAlertsTest.php`, `RequestsTest.php`, `Laravel\Fortify\Features`, `DashboardTest.php`, `ExpirationMonitoringTest.php`, `HeaderTest.php`, `InventoryAdjustmentsFactory`, `ReportsTest.php`, `Stocks`, `Livewire\Livewire`, `DonationReceivingTest.php`, `SecurityTest.php`, `PasswordResetTest.php`, `Profile`, `PasswordValidationRules`, `ProfileUpdateTest.php`, `StorageLocations`, `EmailVerificationTest.php`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.063) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07254623044096728 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `InventoryBatches` to `ApprovalsTest.php`, `AdjustmentType`, `AdjustmentCreateForm`, `App\Livewire\Forms\StockReceiptsForm`, `Illuminate\Database\Eloquent\Factories\Factory`, `StockReconciliationTest.php`, `Illuminate\Database\Eloquent\Relations\HasMany`, `StockReconciliation`, `PartialDeliveryTest.php`, `InventoryTransactions`, `StockStatusAlertsTest.php`, `RequestsTest.php`, `Illuminate\Support\Facades\DB`, `User`, `DashboardTest.php`, `ReceivingTest.php`, `ExpirationMonitoring`, `ExpirationMonitoringTest.php`, `InventoryAdjustmentsFactory`, `ReportsTest.php`, `Livewire\Livewire`, `DonationReceivingTest.php`, `Livewire\Form`, `App\Livewire\Inventory\Receiving`, `IssuancesTest.php`, `StorageLocations`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **Should `AdjustmentType` be split into smaller, more focused modules?**
  _Cohesion score 0.11083743842364532 - nodes in this community are weakly interconnected._