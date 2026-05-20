# MIS Inventory System

## Summary

The inventory system tracks MIS equipment as main inventory items, then tracks the components installed inside or attached to each item.

Think of it like this:

- An **Inventory Item** is the main equipment record, such as a desktop computer, laptop, printer, UPS, or monitor set.
- A **Component** is a replaceable part connected to that item, such as RAM, SSD, HDD, keyboard, mouse, power supply, monitor, or battery.
- A **History Record** is the audit trail that explains what happened to a component.

The current process is designed as an asset lifecycle:

1. Register the main equipment.
2. Install active components.
3. Delete component records only when they were added by mistake.
4. Mark components as damaged when they fail.
5. Replace components when a new part is installed.
6. Review history for audit and troubleshooting.

## Why The Flow Was Designed This Way

The important design rule is:

> Users should not manually choose lifecycle statuses for components during installation.

When a component is installed, the system makes it `active` automatically. Later, the component status changes only through real actions:

- **Mark Damaged** changes an active component to `damaged`.
- **Replace** changes the old component to `replaced` and creates a new `active` component.
- If another active component with the same name exists during replacement logic, older active duplicates can be moved away from active status.

This makes the data easier to trust because component status comes from a recorded event, not from a random dropdown selection.

## Main User Flow

### 1. Register Equipment

Open **MIS Item Inventory**.

Click **Add Item** and fill in:

- Item Code
- Item Name
- Item Type
- Assigned To
- Status
- Department
- Section/Location
- Remarks

This creates a row in `inventory_items`.

### 2. Open The Equipment Workspace

Click **View Components** from the inventory list.

The equipment workspace shows:

- The main equipment details
- Component totals
- Active component count
- Damaged component count
- History record count
- A **Components** tab
- A **History** tab

### 3. Install A Component

Inside the equipment workspace, click **Install Component**.

Fill in:

- Component Name
- Serial Number
- Brand
- Model
- Remarks
- Installation Note

The status is not manually selected. The backend sets the installed component to `active`.

This creates a row in `inventory_item_parts`.

### 4. Edit Component Details

Use the edit icon in the component row.

Editing is for correcting details only:

- Component name
- Serial number
- Brand
- Model
- Remarks

Editing does not create a history record because it is not treated as a lifecycle event.

### 5. Delete A Mistaken Component Record

Use the delete icon in the component row only when a component was entered by mistake.

Delete removes the component record. It is not the normal maintenance flow.

Use **Mark Damaged** or **Replace** when something actually happened to a real component, because those actions preserve the audit trail.

### 6. Mark A Component As Damaged

Use the damage icon in the component row.

The system asks for:

- Reason
- Remarks

The backend changes the component status to `damaged` and creates a history record in `inventory_part_histories`.

Only active components can be marked as damaged.

### 7. Replace A Component

Use the replace icon in the component row.

The system asks for new component details:

- Component name
- New serial number
- Brand
- Model
- Reason
- New component remarks
- Replacement history note

The backend:

1. Marks the old component as `replaced`.
2. Creates a new component as `active`.
3. Creates a replacement history record.

Inactive or already replaced components cannot be replaced again.

### 8. View Component History

Use the history icon in the component row.

The page switches to the **History** tab and filters records for that component name.

Click **Show All** to remove the filter.

## Files

### Routes

Inventory routes live in:

- `routes/web.php`

Route group:

```php
Route::middleware(['auth'])->prefix('inventory-items')->name('inventory-items.')->controller(InventoryItemController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::put('/{inventoryItem}', 'update')->name('update');
    Route::get('/{inventoryItem}', 'show')->name('show');
    Route::get('/{inventoryItem}/history', 'history')->name('history');
    Route::post('/{inventoryItem}/parts', 'storePart')->name('parts.store');
    Route::put('/{inventoryItem}/parts/{inventoryItemPart}', 'updatePart')->name('parts.update');
    Route::delete('/{inventoryItem}/parts/{inventoryItemPart}', 'destroyPart')->name('parts.destroy');
    Route::post('/{inventoryItem}/parts/{inventoryItemPart}/damage', 'markPartAsDamaged')->name('parts.damage');
    Route::post('/{inventoryItem}/parts/{inventoryItemPart}/replace', 'replacePart')->name('parts.replace');
});
```

### Controller

- `app/Http/Controllers/InventoryItemController.php`

This controller handles:

- listing inventory items
- creating inventory items
- updating inventory items
- showing one item workspace
- installing components
- editing component details
- deleting mistaken component records
- marking components as damaged
- replacing components
- loading history

### Models

- `app/Models/InventoryItem.php`
- `app/Models/InventoryItemPart.php`
- `app/Models/InventoryPartHistory.php`

### Request Validation

- `app/Http/Requests/StoreInventoryItemRequest.php`
- `app/Http/Requests/UpdateInventoryItemRequest.php`
- `app/Http/Requests/StoreInventoryItemPartRequest.php`
- `app/Http/Requests/UpdateInventoryItemPartRequest.php`
- `app/Http/Requests/MarkInventoryItemPartDamagedRequest.php`
- `app/Http/Requests/ReplaceInventoryItemPartRequest.php`

Request classes keep validation rules out of the controller.

### Views

- `resources/views/inventory-items/index.blade.php`
- `resources/views/inventory-items/show.blade.php`
- `resources/views/inventory-items/partials/table.blade.php`
- `resources/views/inventory-items/partials/parts-table.blade.php`
- `resources/views/inventory-items/partials/history-table.blade.php`
- `resources/views/inventory-items/partials/status-badge.blade.php`

### JavaScript

- `public/assets/js/custom/inventory-items.js`

This file handles:

- opening modals
- filling edit forms
- submitting AJAX forms
- refreshing the component table
- refreshing the history table
- switching to the History tab
- showing inline feedback
- reinitializing DataTables
- initializing Bootstrap tooltips

### Migrations

- `database/migrations/2026_04_27_000003_create_inventory_items_table.php`
- `database/migrations/2026_04_27_000004_create_inventory_item_parts_table.php`
- `database/migrations/2026_04_27_000005_create_inventory_part_histories_table.php`

## Data Model

### `inventory_items`

Stores the main equipment record.

Important columns:

- `item_code`
- `item_name`
- `item_type`
- `assigned_to`
- `department`
- `location`
- `remarks`
- `status`

### `inventory_item_parts`

Stores installed or previously installed components.

Important columns:

- `inventory_item_id`
- `part_name`
- `serial_number`
- `brand`
- `model`
- `remarks`
- `status`
- `installed_at`
- `removed_at`

### `inventory_part_histories`

Stores lifecycle events for components.

Important columns:

- `inventory_item_id`
- `old_part_id`
- `new_part_id`
- `part_name`
- `action_type`
- `reason`
- `remarks`
- `action_date`
- `performed_by`

## Backend Flow For Beginners

### Example: Installing A Component

1. User clicks **Install Component**.
2. The modal form opens.
3. User submits the form.
4. JavaScript sends an AJAX `POST` request to:

```text
/inventory-items/{inventoryItem}/parts
```

5. Laravel routes the request to:

```php
InventoryItemController::storePart()
```

6. `StoreInventoryItemPartRequest` validates the form.
7. The controller creates a new `InventoryItemPart`.
8. The component is saved as `active`.
9. The controller returns JSON.
10. JavaScript refreshes the component table without reloading the whole page.

### Example: Replacing A Component

1. User clicks the replace icon.
2. The replacement modal opens.
3. User enters new component details and reason.
4. JavaScript sends an AJAX `POST` request to:

```text
/inventory-items/{inventoryItem}/parts/{inventoryItemPart}/replace
```

5. Laravel routes the request to:

```php
InventoryItemController::replacePart()
```

6. The controller starts a database transaction.
7. The old component becomes `replaced`.
8. A new component is created as `active`.
9. A history record is created.
10. JavaScript refreshes the component and history tables.

## Frontend Flow For Beginners

The Blade files render the HTML.

The JavaScript file makes the page interactive.

For example:

- `.edit-inventory-part` buttons open the component modal in edit mode.
- `.mark-damaged-part` buttons open the damage modal.
- `.replace-inventory-part` buttons open the replacement modal.
- `.view-part-history` buttons switch to the History tab and filter the history table.

The page avoids full reloads for common actions. Instead, it reloads only partial Blade views:

- `inventory-items.partials.parts-table`
- `inventory-items.partials.history-table`

That is why the user sees the table update quickly after saving.

## Access Control

The controller calls:

```php
$this->ensureAccess();
```

That checks:

```php
auth()->user()?->canManageInventory()
```

Only users allowed by `canManageInventory()` should access inventory screens and actions.

## Current Rules

- Item code cannot be changed after creation.
- Component serial numbers must be unique when provided.
- New installed components default to `active`.
- Only active components can be marked as damaged.
- Inactive or replaced components cannot be replaced again.
- Replacement creates a new active component and stores an audit history record.

## Suggested Next Improvements

- Add an item-level history table for changes to assigned person, department, location, and item status.
- Add QR code or barcode labels for item codes.
- Add export to Excel or PDF.
- Add component categories such as storage, memory, power, display, network, peripheral.
- Add warranty dates and supplier details.
- Add role-specific permissions for view-only users.
- Add a dashboard for damaged components that need action.
