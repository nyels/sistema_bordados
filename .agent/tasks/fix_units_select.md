# Task: Fix Units Select in Creation Form

## User Request
"no salen las unidades de consumo en el select en admin/units/create"
( The consumption units do not appear in the select dropdown in admin/units/create )

## Root Cause Analysis
1.  **Backend (`UnitController.php`)**: The `create` method passes the variable `$canonicalUnits` to the view.
    ```php
    $canonicalUnits = Unit::getCanonicalUnits();
    return view('admin.units.create', compact('canonicalUnits'));
    ```
2.  **Frontend (`create.blade.php`)**: The view currently iterates over a nonexistent variable `$baseUnits` to populate the dropdown.
    ```blade
    @foreach ($baseUnits ?? [] as $baseUnit)
    ```
3.  **Frontend (`_form.blade.php`)**: The user has a partial `_form.blade.php` (currently open) which correctly uses `$canonicalUnits`.
    ```blade
    @foreach($canonicalUnits ?? [] as $canonicalUnit)
    ```

## Proposed Solution
Refactor both `create.blade.php` and `edit.blade.php` to use the `admin.units._form` partial. This ensures:
1.  The view uses the correct variable `$canonicalUnits` passed by the controller.
2.  Code duplication is reduced.
3.  The form reflects the latest backend logic (specifically regarding `unit_type` determination via conversion factor, which matches the new `_form.blade.php` logic).

## Implementation Steps
1.  **Modify `resources/views/admin/units/create.blade.php`**: Replace the inline form with `@include('admin.units._form')`.
2.  **Modify `resources/views/admin/units/edit.blade.php`**: Replace the inline form with `@include('admin.units._form')`.
3.  **Verify**: Ensure the controller sends the correct data (confirmed in analysis).

## Verification Plan
- Check code syntax (deterministic fix).
- User can verify by refreshing the page; the select dropdown should now be populated.
