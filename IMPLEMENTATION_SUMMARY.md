# Lesson Agreement System Implementation Summary

## Overview
This document summarizes the implementation of the lesson agreement system with automatic application messages and Turkish center support.

## Database Changes Required

### 1. Create Turkish Centers Table
**File:** `database/migrations/create_turkish_centers.sql`

Execute this SQL on your production database to create the `turkish_centers` table with sample data for 20 Turkish institutions across major German cities.

### 2. Add Turkish Center ID to Lesson Agreements
**File:** `database/migrations/add_turkish_center_id_to_lesson_agreements.sql`

Execute this SQL to add the `turkish_center_id` foreign key field to the `lesson_agreements` table.

## Backend Changes

### 1. Turkish Centers API
**New File:** `server/api/centers/list.php`

**Purpose:** Fetch list of Turkish centers, optionally filtered by city

**Endpoint:** `GET /server/api/centers/list.php`

**Query Parameters:**
- `city` (optional): Filter centers by city
- `active_only` (optional, default: true): Only return active centers

**Response:**
```json
{
  "success": true,
  "data": {
    "centers": [
      {
        "id": 1,
        "name": "Türkisch-Deutsches Zentrum Berlin",
        "city": "Berlin",
        "address": "Potsdamer Str. 96",
        "zip_code": "10785",
        "contact_person": "Ahmet Yılmaz",
        "phone": "+49 30 12345678",
        "email": "info@tdz-berlin.de",
        "is_active": true
      }
    ],
    "cities": ["Berlin", "Hamburg", "München", ...],
    "total": 20
  }
}
```

### 2. Automatic Application Messages
**Modified File:** `server/api/messages/start.php`

**Changes:**
- Added `request_title` and `request_id` parameters to input
- When teacher applies to lesson request, automatically sends first message:
  - Format: "Merhaba, '[Talep Başlığı]' ders talebinize başvurmak istiyorum.\n\n- [Öğretmen Adı]"

**Frontend Integration Required:**
The lesson request detail page now sends these additional parameters when starting a conversation.

### 3. Lesson Agreement Creation
**Modified File:** `server/api/agreements/create.php`

**Changes:**
- Added support for `turkish_center_id` parameter
- Validates Turkish center selection when `lesson_location` is `turkish_center`
- Automatically fetches center address and stores it in `lesson_address`
- Enhanced validation:
  - `student_home` requires `student_address`
  - `turkish_center` requires `turkish_center_id`
  - `online` auto-generates Jitsi link if not provided

**New Request Parameters:**
```json
{
  "conversation_id": 123,
  "subject_id": 5,
  "hourly_rate": 25.00,
  "hours_per_week": 2,
  "lesson_location": "turkish_center",  // or "student_home" or "online"
  "turkish_center_id": 3,  // Required when lesson_location is "turkish_center"
  "student_address": "...",  // Required when lesson_location is "student_home"
  "start_date": "2025-01-15",
  "notes": "Optional notes"
}
```

### 4. Lesson Agreement Listing
**Modified File:** `server/api/agreements/list.php`

**Changes:**
- Added LEFT JOIN with `turkish_centers` table
- Response now includes:
  - `turkish_center_id`
  - `turkish_center_name`
  - `turkish_center_city`
  - `turkish_center_address`

## Frontend Changes

### 1. Lesson Request Detail Page
**Modified File:** `src/routes/ders-talepleri/[id]/+page.svelte`

**Changes:**
- Updated `handleStartConversation()` to send `request_title` and `request_id` when applying
- Added debug logging for troubleshooting

**Result:**
When teacher clicks "İletişime Geç" button, an automatic application message is now sent to the parent.

## Deployment Checklist

### Database (Production Server)
- [ ] Execute `database/migrations/create_turkish_centers.sql`
- [ ] Execute `database/migrations/add_turkish_center_id_to_lesson_agreements.sql`

### Backend API (api.dijitalmentor.de)
- [ ] Upload `server/api/centers/list.php`
- [ ] Upload updated `server/api/messages/start.php`
- [ ] Upload updated `server/api/agreements/create.php`
- [ ] Upload updated `server/api/agreements/list.php`

### Frontend (Vercel)
The frontend changes are automatically deployed via Vercel when pushed to the repository.

## Testing Steps

### 1. Test Turkish Centers API
```bash
curl https://api.dijitalmentor.de/server/api/centers/list.php
curl https://api.dijitalmentor.de/server/api/centers/list.php?city=Berlin
```

### 2. Test Automatic Application Message
1. Login as a teacher (student role)
2. Navigate to a lesson request detail page
3. Click "İletişime Geç" button
4. Verify you're redirected to the messages page
5. Verify the automatic application message appears

### 3. Test Lesson Agreement with Turkish Center
1. Start a conversation between teacher and parent
2. Create a lesson agreement with `lesson_location: "turkish_center"`
3. Select a Turkish center from the list
4. Verify the agreement is created with center information
5. Verify the center's full address is stored

## Features Implemented

✅ Turkish centers database with 20 sample institutions
✅ API endpoint to list Turkish centers (filterable by city)
✅ Automatic application message when teacher applies to lesson request
✅ Support for Turkish center selection in lesson agreements
✅ Auto-generation of Jitsi links for online lessons
✅ Enhanced validation for location-specific requirements
✅ Foreign key relationship between lesson agreements and Turkish centers

## Next Steps (Future Enhancement)

The following features are planned but not yet implemented:

1. **Frontend UI Components:**
   - Lesson agreement form modal component
   - Turkish center dropdown selector
   - Agreement status cards in messaging interface
   - "Ders Anlaşması Gönder" button in conversation view

2. **Parent → Teacher Messaging:**
   - Add "Mesaj Gönder" button to teacher profile pages
   - Enable parents to initiate conversations with teachers

3. **Agreement Notifications:**
   - Email notifications when agreement is proposed
   - Email notifications when agreement is accepted/rejected
   - In-app notification system

4. **Turkish Centers Management:**
   - Admin panel to manage Turkish centers (CRUD operations)
   - Ability to mark centers as inactive
   - Upload center photos

## API Documentation

### GET /server/api/centers/list.php
Lists Turkish institutions/centers available for lessons.

**Query Parameters:**
- `city` (string, optional): Filter by city name
- `active_only` (boolean, optional, default: true): Only show active centers

**Response:** Array of center objects with full details

### POST /server/api/messages/start.php
Creates a new conversation between teacher and parent.

**Request Body:**
```json
{
  "other_user_id": 456,
  "request_title": "Matematik Özel Ders",  // Optional, triggers auto-message
  "request_id": 789  // Optional, for reference
}
```

### POST /server/api/agreements/create.php
Creates a new lesson agreement proposal.

**Request Body:**
```json
{
  "conversation_id": 123,
  "subject_id": 5,
  "hourly_rate": 25.00,
  "hours_per_week": 2,
  "lesson_location": "turkish_center",
  "turkish_center_id": 3,
  "start_date": "2025-01-15",
  "notes": "Optional notes"
}
```

### GET /server/api/agreements/list.php
Lists all lesson agreements for a conversation.

**Query Parameters:**
- `conversation_id` (required): The conversation ID

**Response:** Array of agreement objects with Turkish center details if applicable

### POST /server/api/agreements/respond.php
Accept, reject, or cancel a lesson agreement.

**Request Body:**
```json
{
  "agreement_id": 123,
  "status": "accepted"  // or "rejected" or "cancelled"
}
```

## Files Modified/Created

### New Files:
- `database/migrations/create_turkish_centers.sql`
- `database/migrations/add_turkish_center_id_to_lesson_agreements.sql`
- `server/api/centers/list.php`
- `IMPLEMENTATION_SUMMARY.md` (this file)

### Modified Files:
- `server/api/messages/start.php`
- `server/api/agreements/create.php`
- `server/api/agreements/list.php`
- `src/routes/ders-talepleri/[id]/+page.svelte`

## Rollback Plan

If issues occur, rollback in this order:

1. **Frontend:** Revert commit in git, Vercel will auto-deploy previous version
2. **Backend:** Restore previous versions of PHP files from backup
3. **Database:**
   ```sql
   ALTER TABLE lesson_agreements DROP FOREIGN KEY fk_lesson_agreement_turkish_center;
   ALTER TABLE lesson_agreements DROP COLUMN turkish_center_id;
   DROP TABLE turkish_centers;
   ```

## Support

For questions or issues, refer to:
- Backend error logs: Check server PHP error logs
- Frontend errors: Check browser console and Vercel deployment logs
- Database errors: Check MySQL error logs
