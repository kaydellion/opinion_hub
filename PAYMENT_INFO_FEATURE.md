# Agent Payment Information Update Feature

## Overview
Added the ability for agents to update their payment information directly from their profile page. This allows agents to manage their bank account details, mobile money information, and preferred payout method without needing to enter it every time they request a payout.

## Changes Made

### 1. Profile Page (profile.php)
Added a new "Payment Information" section for agents that includes:

#### Fields Added:
- **Preferred Payout Method** (dropdown)
  - Bank Transfer
  - Mobile Money
  - Airtime
  - Data Bundle

- **Bank Transfer Details**
  - Bank Name (dropdown with 22 Nigerian banks)
  - Account Name (text input)
  - Account Number (10-digit number input)

- **Mobile Money Details**
  - Mobile Money Provider (MTN, Airtel, 9mobile, Glo)
  - Mobile Number (11-digit number input)

#### Features:
- Only visible to users with `role = 'agent'`
- Visual emphasis on selected payment method (opacity adjustment)
- Both bank and mobile money sections always visible for flexibility
- Informational alert about keeping payment information updated

### 2. Actions Handler (actions.php)

#### Updated `handleUpdateProfile()` function:
- Added payment information fields to the form data capture
- Updated SQL query to save payment fields:
  - `payment_preference`
  - `bank_name`
  - `account_name`
  - `account_number`
  - `mobile_money_provider` (with column existence check)
  - `mobile_money_number` (with column existence check)

#### Updated `ensureUsersTableColumns()` function:
- Added `mobile_money_provider` VARCHAR(50)
- Added `mobile_money_number` VARCHAR(15)
- Updated `payment_preference` ENUM to include: 'bank_transfer', 'mobile_money', 'airtime', 'data'
- Removed default value from payment_preference (was 'cash', now NULL by default)

### 3. Database Schema (add_payment_columns.sql)
Created SQL script to add new columns:
```sql
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS mobile_money_provider VARCHAR(50),
ADD COLUMN IF NOT EXISTS mobile_money_number VARCHAR(15);

ALTER TABLE users 
MODIFY COLUMN payment_preference ENUM('bank_transfer', 'mobile_money', 'airtime', 'data');
```

## How It Works

### For Agents:
1. Navigate to Profile page
2. Scroll to "Payment Information" section
3. Select preferred payout method
4. Fill in relevant payment details:
   - For Bank Transfer: bank name, account name, account number
   - For Mobile Money: provider, mobile number
5. Click "Update Profile"
6. Payment information is saved and will be used for future payout requests

### For Developers:
- Column existence checks ensure backward compatibility
- Mobile money fields are only updated if columns exist in database
- Visual feedback via opacity helps users focus on relevant fields
- Form validation uses HTML5 patterns (10 digits for account, 11 for mobile)

## Database Columns Used

### Existing Columns:
- `bank_name` - VARCHAR
- `account_name` - VARCHAR
- `account_number` - VARCHAR
- `payment_preference` - ENUM (updated values)

### New Columns:
- `mobile_money_provider` - VARCHAR(50)
- `mobile_money_number` - VARCHAR(15)

## Integration with Existing Features

### Payout Request System:
- Agent payout requests (agent/request-payout.php) can pre-fill payment details from profile
- Admin payout management (admin/manage-payouts.php) can reference saved payment info
- Agent dashboard shows saved payment information

### Profile Completion Flow:
- Payment info is optional during initial profile setup
- Can be added/updated anytime from profile page
- No impact on existing agent registration workflow

## User Experience Improvements

1. **Convenience**: Agents don't need to re-enter payment details for every payout
2. **Flexibility**: All payment sections remain visible so agents can maintain multiple payment methods
3. **Visual Clarity**: Selected payment method is highlighted with full opacity
4. **Data Validation**: HTML5 patterns enforce correct formats for account/mobile numbers
5. **Backward Compatible**: Works with existing database schemas via column existence checks

## Testing Checklist

- [ ] Agent can view Payment Information section on profile page
- [ ] Client users do NOT see Payment Information section
- [ ] Bank name dropdown populates with 22 Nigerian banks
- [ ] Account number field accepts only 10 digits
- [ ] Mobile number field accepts only 11 digits
- [ ] Payment preference dropdown shows 4 options
- [ ] Form submission saves all payment fields correctly
- [ ] Mobile money fields save when columns exist in database
- [ ] Visual emphasis changes based on selected payment method
- [ ] Profile update success message displays
- [ ] Saved payment info displays on subsequent profile page loads

## Files Modified

1. `/Applications/XAMPP/xamppfiles/htdocs/opinion/profile.php`
   - Added Payment Information section with form fields
   - Added JavaScript for payment method toggle

2. `/Applications/XAMPP/xamppfiles/htdocs/opinion/actions.php`
   - Updated handleUpdateProfile() to handle payment fields
   - Updated ensureUsersTableColumns() to add new columns
   - Added column existence checks for backward compatibility

3. `/Applications/XAMPP/xamppfiles/htdocs/opinion/add_payment_columns.sql`
   - SQL script to add new columns to users table

## Next Steps (Optional Enhancements)

1. **Pre-fill Payout Requests**: Auto-populate payment details in request-payout.php from saved profile
2. **Payment Method Validation**: Add backend validation for bank account verification
3. **Multiple Accounts**: Allow agents to save multiple bank accounts/mobile numbers
4. **Payment History**: Link payment method changes to audit trail
5. **Default Method**: Add ability to set one payment method as default

## Notes

- The implementation uses column existence checks to ensure compatibility with production databases
- All new database columns have DEFAULT NULL to avoid issues with existing data
- The payment_preference enum was updated to use descriptive names (bank_transfer, mobile_money) instead of generic terms (cash)
- Mobile money fields are stored separately from bank account fields for data integrity
