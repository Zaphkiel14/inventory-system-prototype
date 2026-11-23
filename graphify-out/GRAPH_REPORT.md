# Graph Report - inventory-system-prototype  (2026-11-23)

## Corpus Check
- 254 files · ~59,109 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 22 file(s) not represented in the graph (top: (none) 17, .example 1, .neon 1)

## Summary
- 1480 nodes · 2589 edges · 198 communities (54 shown, 144 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 68 edges (avg confidence: 0.85)
- Token cost: 79,458 input · 0 output

## Community Hubs (Navigation)
- Inventory Transaction Types
- Batch Codes & Factories
- Frontend NPM Dependencies
- Requisition Request Form
- Adjustment Types
- Categories & Items Lists
- Issuance Workflow
- Configuration Screens
- Fortify Views & Bootstrap
- Stock Monitoring Closures
- Item Create/Edit Forms
- Stock Levels & Admin Areas
- Transaction History Filters
- Reports Aggregation
- Usable Batch Scopes
- Security Settings Component
- Requisition & Issuance Models
- Issuances Tests
- Requisition/Issuance Migrations
- Database Seeders
- Approval Decisions
- Stock Receiving Form
- Stocks Browser
- Inventory Adjustment Form
- Expiration Monitoring
- Service Providers
- Approvals Workflow
- Fortify User Actions
- Stock Monitoring Dashboard
- User Model & Auth
- Adjustments Factory
- Approvals Tests
- Stock Receipts & Status
- Batch/Location/Supplier Relations
- JS Charts
- Stock Status Enum
- Reports Closures
- Inventory Batches Model
- Composer Scripts
- Requisition Status Enum
- Adjustments List
- Breadcrumbs Header
- Composer Metadata
- Composer Dev Dependencies
- Deploy Workflow
- Expiration Monitoring Tests
- Category Multi-Select
- Security Blade View
- Profile Settings
- Batch/Category/Txn Forms
- Stock Issuances Model
- Inventory Adjustments Model
- Requests List
- Approvals Model
- Composer Runtime Deps
- Passkeys & Batches Migrations
- Supplier & Receipt Migrations
- Category Create Form
- Expiration Filters
- Two-Factor Recovery Codes
- Attachments & Audit Logs
- Test Harness
- Composer Config
- Adjustment Triggers Migration
- Logout Action
- Users Table Migration
- approvals.blade
- composer
- composer (2)
- UserFactory
- table
- table (2)
- InventoryAdjustmentsSeeder
- stock-monitoring.blade
- expiration-monitoring.blade
- table (3)
- table (4)
- table (5)
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
- requests.blade
- issuances.blade
- recovery-codes.blade
- category-multi-select.blade
- receiving-create-form.blade
- stocks.blade
- request-create-form.blade
- Controller
- apple-touch-icon
- favicon
- header.blade
- sidebar.blade
- card.blade
- simple.blade
- split.blade
- departments.blade
- sections.blade
- storage-locations.blade
- units.blade
- categories.blade
- items.blade
- appearance.blade

## God Nodes (most connected - your core abstractions)
1. `InventoryItems` - 61 edges
2. `InventoryBatches` - 53 edges
3. `AdjustmentType` - 48 edges
4. `User` - 45 edges
5. `InventoryTransactionType` - 35 edges
6. `Requisitions` - 33 edges
7. `InventoryTransactions` - 31 edges
8. `StockReceipts` - 29 edges
9. `RequisitionStatus` - 25 edges
10. `Reports` - 25 edges

## Surprising Connections (you probably didn't know these)
- `{closure#1}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#2}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#3}()` --references_constant--> `AdjustmentType`  [EXTRACTED]
  database/seeders/InventoryAdjustmentsSeeder.php → app/Enums/AdjustmentType.php
- `{closure#1}()` --references--> `InventoryItems`  [EXTRACTED]
  database/seeders/InventoryBatchesSeeder.php → app/Models/InventoryItems.php
- `{closure#2}()` --references--> `InventoryItems`  [EXTRACTED]
  database/seeders/InventoryBatchesSeeder.php → app/Models/InventoryItems.php

## Import Cycles
- None detected.

## Communities (198 total, 144 thin omitted)

### Community 0 - "Inventory Transaction Types"
Cohesion: 0.06
Nodes (37): InventoryTransactionType, Adjustment, Issuance, Receipt, {closure#26}(), {closure#4}(), {closure#7}(), InventoryTransactions (+29 more)

### Community 1 - "Batch Codes & Factories"
Cohesion: 0.05
Nodes (13): BatchCodeGenerator, {closure#1}(), {closure#2}(), {closure#3}(), InventoryBatchesFactory, InventoryCategoriesFactory, InventoryItemsFactory, StockReceiptsFactory (+5 more)

### Community 2 - "Frontend NPM Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, concurrently@^10.0.3, @laravel/passkeys@^0.2.0, laravel-vite-plugin@^3.1, tailwindcss@^4.0.7, @tailwindcss/vite@^4.1.11, vite@npm:@voidzero-dev/vite-plus-core@1.4.0, vite-plus@1.4.0 (+24 more)

### Community 3 - "Requisition Request Form"
Cohesion: 0.09
Nodes (14): RequisitionsForm, RequestCreateForm, {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#3}() (+6 more)

### Community 4 - "Adjustment Types"
Cohesion: 0.12
Nodes (16): AdjustmentType, CountCorrection, Damage, ExpiryWriteOff, Loss, Return, {closure#7}(), adjust() (+8 more)

### Community 5 - "Categories & Items Lists"
Cohesion: 0.08
Nodes (3): Categories, Items, Receiving

### Community 7 - "Configuration Screens"
Cohesion: 0.13
Nodes (6): Departments, Sections, StorageLocations, Units, AuditLogs, Appearance

### Community 8 - "Fortify Views & Bootstrap"
Cohesion: 0.13
Nodes (13): {closure#1}(), {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+5 more)

### Community 9 - "Stock Monitoring Closures"
Cohesion: 0.12
Nodes (11): {closure#1}(), {closure#11}(), {closure#13}(), {closure#14}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}() (+3 more)

### Community 10 - "Item Create/Edit Forms"
Cohesion: 0.13
Nodes (5): {closure#2}(), InventoryItemsForm, ItemCreateForm, ItemEditForm, InventoryCategories

### Community 11 - "Stock Levels & Admin Areas"
Cohesion: 0.16
Nodes (7): {closure#10}(), {closure#12}(), {closure#25}(), {closure#8}(), AdministrativeAreaService, {closure#1}(), {closure#2}()

### Community 12 - "Transaction History Filters"
Cohesion: 0.13
Nodes (3): {closure#12}(), {closure#13}(), TransactionHistory

### Community 14 - "Usable Batch Scopes"
Cohesion: 0.15
Nodes (14): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+6 more)

### Community 16 - "Requisition & Issuance Models"
Cohesion: 0.12
Nodes (3): RequisitionItems, StockIssuanceBatches, StockIssuanceItems

### Community 17 - "Issuances Tests"
Cohesion: 0.20
Nodes (14): approvedRequest(), batchOf(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#15}() (+6 more)

### Community 18 - "Requisition/Issuance Migrations"
Cohesion: 0.15
Nodes (11): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+3 more)

### Community 19 - "Database Seeders"
Cohesion: 0.13
Nodes (6): DatabaseSeeder, InventoryCategoriesSeeder, InventoryItemsSeeder, StockReceiptsSeeder, StorageLocationsSeeder, SuppliersSeeder

### Community 20 - "Approval Decisions"
Cohesion: 0.16
Nodes (7): ApprovalDecision, Approved, Rejected, {closure#2}(), {closure#3}(), {closure#1}(), Requisitions

### Community 21 - "Stock Receiving Form"
Cohesion: 0.14
Nodes (3): {closure#1}(), StockReceiptsForm, ReceivingCreateForm

### Community 24 - "Inventory Adjustment Form"
Cohesion: 0.18
Nodes (4): {closure#1}(), InventoryAdjustmentsForm, AdjustmentCreateForm, {closure#8}()

### Community 26 - "Service Providers"
Cohesion: 0.16
Nodes (3): AppServiceProvider, {closure#1}(), FortifyServiceProvider

### Community 28 - "Fortify User Actions"
Cohesion: 0.21
Nodes (4): CreateNewUser, ResetUserPassword, PasswordValidationRules, DeleteUserForm

### Community 31 - "Adjustments Factory"
Cohesion: 0.20
Nodes (4): {closure#1}(), {closure#2}(), {closure#4}(), InventoryAdjustmentsFactory

### Community 32 - "Approvals Tests"
Cohesion: 0.21
Nodes (11): {closure#10}(), {closure#11}(), {closure#12}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+3 more)

### Community 33 - "Stock Receipts & Status"
Cohesion: 0.14
Nodes (5): ReceiptStatus, Complete, Partial, {closure#9}(), StockReceipts

### Community 34 - "Batch/Location/Supplier Relations"
Cohesion: 0.19
Nodes (3): {closure#1}(), StorageLocations, Suppliers

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
Cohesion: 0.21
Nodes (10): {closure#22}(), {closure#23}(), InventoryBatches, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}() (+2 more)

### Community 40 - "Composer Scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 41 - "Requisition Status Enum"
Cohesion: 0.18
Nodes (8): RequisitionStatus, Approved, Cancelled, Issued, PartiallyIssued, Pending, Rejected, {closure#9}()

### Community 43 - "Breadcrumbs Header"
Cohesion: 0.26
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

### Community 48 - "Expiration Monitoring Tests"
Cohesion: 0.30
Nodes (10): {closure#10}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+2 more)

### Community 50 - "Security Blade View"
Cohesion: 0.18
Nodes (10): closeDeleteModal, confirmDelete, confirmTwoFactor, disable, enable, resetVerification, deletePasskey, partials.settings-heading (+2 more)

### Community 60 - "Composer Runtime Deps"
Cohesion: 0.22
Nodes (9): require, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, livewire/blaze, livewire/flux, livewire/livewire (+1 more)

### Community 65 - "Expiration Filters"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}()

### Community 70 - "Composer Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 71 - "Adjustment Triggers Migration"
Cohesion: 0.38
Nodes (5): {closure#1}(), {closure#2}(), mysqlTriggers(), sqliteTriggers(), up()

### Community 73 - "Users Table Migration"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 74 - "approvals.blade"
Cohesion: 0.40
Nodes (4): approve, fillMaximum, reject, review

### Community 75 - "composer"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 76 - "composer (2)"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

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

## Knowledge Gaps
- **91 isolated node(s):** `Damage`, `Loss`, `ExpiryWriteOff`, `CountCorrection`, `Return` (+86 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 617 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **144 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User Model & Auth` to `Inventory Transaction Types`, `Requisition Request Form`, `Adjustment Types`, `Usable Batch Scopes`, `Requisition & Issuance Models`, `Issuances Tests`, `Database Seeders`, `Fortify User Actions`, `Adjustments Factory`, `Approvals Tests`, `Batch/Location/Supplier Relations`, `Inventory Batches Model`, `Breadcrumbs Header`, `Fortify Auth Tests`, `Expiration Monitoring Tests`, `Email Verification Tests`, `Password Reset Tests`, `Security Tests`, `Framework Config`, `InventoryAdjustmentsSeeder`, `PasswordValidationRules`, `TwoFactorChallengeTest`, `DashboardTest`?**
  _High betweenness centrality (0.096) - this node is a cross-community bridge._
- **What connects `Damage`, `Loss`, `ExpiryWriteOff` to the rest of the system?**
  _91 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Inventory Transaction Types` be split into smaller, more focused modules?**
  _Cohesion score 0.055944055944055944 - nodes in this community are weakly interconnected._
- **Why does `InventoryItems` connect `Stock Monitoring Closures` to `Inventory Transaction Types`, `Batch Codes & Factories`, `Requisition Request Form`, `Adjustment Types`, `Categories & Items Lists`, `Issuance Workflow`, `Item Create/Edit Forms`, `Usable Batch Scopes`, `Issuances Tests`, `Database Seeders`, `Stock Receiving Form`, `Stock Monitoring Dashboard`, `Approvals Tests`, `Batch/Location/Supplier Relations`, `Stock Status Enum`, `Livewire Form Imports`, `Reports Closures`, `Expiration Monitoring Tests`, `Attachments & Audit Logs`?**
  _High betweenness centrality (0.077) - this node is a cross-community bridge._
- **Should `Batch Codes & Factories` be split into smaller, more focused modules?**
  _Cohesion score 0.0514216575922565 - nodes in this community are weakly interconnected._
- **Why does `InventoryBatches` connect `Inventory Batches Model` to `Inventory Transaction Types`, `Batch Codes & Factories`, `Requisition Request Form`, `Adjustment Types`, `Issuance Workflow`, `Stock Monitoring Closures`, `Usable Batch Scopes`, `Requisition & Issuance Models`, `Issuances Tests`, `Requisition Page Closures`, `Stocks Browser`, `Inventory Adjustment Form`, `Adjustments Factory`, `Approvals Tests`, `Batch/Location/Supplier Relations`, `Livewire Form Imports`, `Reports Closures`, `Expiration Monitoring Tests`, `Batch/Category/Txn Forms`, `Expiration Filters`, `Attachments & Audit Logs`, `InventoryAdjustmentsSeeder`?**
  _High betweenness centrality (0.047) - this node is a cross-community bridge._
- **Should `Frontend NPM Dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07899159663865546 - nodes in this community are weakly interconnected._