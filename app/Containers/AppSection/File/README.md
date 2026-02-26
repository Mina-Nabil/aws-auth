# File Container

## Overview

The File Container is a comprehensive file management system that handles file uploads, storage, retrieval, and linking to different entities within the application. It provides a flexible polymorphic relationship system that allows any entity to have files attached to it.

## Key Features

- **File Upload & Storage**: Secure file upload with support for multiple file types
- **Polymorphic File Links**: Attach files to any entity (Users, Jobs, Projects, etc.)
- **Access Control**: Fine-grained permission system for file operations
- **Audit Trail**: Complete audit logging of all file operations
- **Visibility Control**: Public, private, and share-link visibility options
- **Metadata Management**: File descriptions, tags, and search capabilities
- **Temporary URLs**: Secure time-limited access to private files

## APIs Used

### File CRUD APIs

#### 1. List Files
```
GET /files
```
**Permission Required**: `view-any-file`

**Query Parameters**:
- `page`: Page number for pagination
- `limit`: Items per page
- `q`: Search query (name or description)
- `tags`: Filter by tags (comma-separated)
- `file_type`: Filter by file type, one of: `image`, `video`, `archive`, `pdf`, `document`
- `uploader_id`: Filter by uploader
- `entity_type`: Filter by linked entity type
- `entity_id`: Filter by linked entity ID
- `from`: Files uploaded after date
- `to`: Files uploaded before date
- `include`: Include relationships (uploaded_by, entities)

#### 2. Find File by ID
```
GET /files/{file_id}
```
**Permission Required**: `show-file` or `download-any-file`

**Query Parameters**:
- `include`: Include relationships

#### 3. Download File
```
GET /files/{file_id}/download
```
**Permission Required**: `show-file` or `download-any-file`

Returns a temporary signed URL for file download.

#### 4. Upload File
```
POST /files
```
**Permission Required**: `create-file`

**Request Body** (multipart/form-data):
- `file`: File to upload (required)
- `description`: File description (optional)
- `tags`: Array of tags (optional)
- `visibility`: public|private|share_link (required)
- `checksum`: File checksum for verification (optional)
- `uploaded_by`: UUID of uploader (required)

#### 5. Update File
```
PATCH /files/{file_id}
```
**Permission Required**: `update-file`

**Request Body**:
- `description`: Updated description
- `tags`: Updated tags
- `visibility`: Updated visibility

#### 6. Delete File
```
DELETE /files/{file_id}
```
**Permission Required**: `delete-file`

Permanently deletes a file from the system.

---

### File Link APIs

#### 1. List Entity Files
```
GET /{entity_type}/{entity_id}/files
```
**Permission Required**: Entity-specific permission (e.g., `list-user-files`)

**Query Parameters**:
- `type`: Filter files by file type, one of: `image`, `video`, `archive`, `pdf`, `document`
- `include`: Comma-separated list of relationships to include (entities, uploaded_by)

#### 2. Attach File to Entity
```
POST /{entity_type}/{entity_id}/files
```
**Permission Required**: Entity-specific permission (e.g., `attach-user-files`)

**Request Body**:
- `file_id`: UUID of the file to attach

#### 3. Detach File from Entity
```
DELETE /{entity_type}/{entity_id}/files/{file_id}
```
**Permission Required**: Entity-specific permission (e.g., `detach-user-files`)

---

## Container Configurations

### Configuration File

**Location**: `Configs/appSection-file.php`

```php
return [
    // Maximum file size in kilobytes (default: 10MB)
    'max_file_size' => env('MAX_FILE_SIZE', 10000),
    
    // Default storage directory
    'default_directory' => env('DEFAULT_DIRECTORY', 'files'),
    
    // Temporary URL duration in minutes (default: 5 minutes)
    'temporary_url_duration' => env('TEMPORARY_URL_DURATION', 5),
    
    // Allowed entities that can have files attached
    'allowed_entities' => [User::MORPH_CLASS],
];
```

### Service Provider

**Location**: `Providers/FileServiceProvider.php`

The service provider registers all file-related gates:

```php
Gate::define(FileGate::CREATE_FILE, [FilePolicy::class, 'create']);
Gate::define(FileGate::VIEW_ANY_FILE, [FilePolicy::class, 'viewAny']);
Gate::define(FileGate::VIEW_FILE, [FilePolicy::class, 'view']);
Gate::define(FileGate::DOWNLOAD_ANY_FILE, [FilePolicy::class, 'downloadAny']);
Gate::define(FileGate::UPDATE_FILE, [FilePolicy::class, 'update']);
Gate::define(FileGate::DELETE_FILE, [FilePolicy::class, 'delete']);
```

### File Type Filtering

The File Container supports filtering files by type using the `file_type` parameter (for `/files` endpoint) or `type` parameter (for entity file endpoints). Both parameters accept the following values:

- **`image`**: JPEG, PNG, GIF, WebP files
- **`video`**: MP4, MPEG, FLV, MOV files
- **`archive`**: ZIP, 7z, RAR, TAR, GZIP, BZIP2 files
- **`pdf`**: PDF files
- **`document`**: MS Office (Word, Excel, PowerPoint), Text, CSV files

**Examples**:
```bash
# List only image files
GET /files?file_type=image

# List only PDF files for a specific entity
GET /user/{user_id}/files?type=pdf
```

### Allowed File Types

The following MIME types are allowed:

- **Documents**: PDF, MS Office (Word, Excel, PowerPoint), Text, CSV
- **Images**: JPEG, PNG, GIF, WebP
- **Archives**: ZIP, 7z, RAR, TAR, GZIP, BZIP2
- **Videos**: MP4, MPEG, FLV, MOV

### File Visibility Options

1. **public**: File is publicly accessible via direct URL
2. **private**: File requires authentication and temporary signed URLs
3. **share_link**: File can be shared via a secure link

---

## Permissions Setup

### File Container Permissions

**Location**: `permissions.json`

| Permission | Display Name | Description | Default Roles |
|------------|--------------|-------------|---------------|
| `view-any-file` | View any file | View any file metadata | super-admin, admin |
| `download-any-file` | Download any file | Download any file | super-admin, admin |
| `show-file` | View file | View & download file if linked to accessible entity | super-admin, admin, user |
| `create-file` | Create file | Create & upload a file | super-admin, admin |
| `update-file` | Update file | Update file metadata | super-admin, admin |
| `delete-file` | Delete file | Delete a file | super-admin, admin |

### Implementing File Permissions for Other Entities

To enable file attachments for your entity (e.g., Job, Project, Document), follow these steps:

#### Step 1: Add Entity to Allowed Entities

Update `Configs/appSection-file.php`:

```php
'allowed_entities' => [
    User::MORPH_CLASS,
    Job::MORPH_CLASS,  // Add your entity
],
```

#### Step 2: Add Entity-Specific Permissions

Create or update your entity's `permissions.json`:

```json
[
    {
        "id": "unique-uuid-1",
        "name": "list-job-files",
        "display_name": "List job files",
        "description": "List specific job files",
        "group": "jobs",
        "default_roles": ["super-admin", "admin", "user"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "unique-uuid-2",
        "name": "attach-any-job-files",
        "display_name": "Attach any job files",
        "description": "Attach files to any job",
        "group": "jobs",
        "default_roles": ["super-admin", "admin"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "unique-uuid-3",
        "name": "attach-job-files",
        "display_name": "Attach job files",
        "description": "Attach files to accessible jobs only",
        "group": "jobs",
        "default_roles": ["super-admin", "admin", "user"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "unique-uuid-4",
        "name": "detach-any-job-files",
        "display_name": "Detach any job files",
        "description": "Detach files from any job",
        "group": "jobs",
        "default_roles": ["super-admin", "admin"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "unique-uuid-5",
        "name": "detach-job-files",
        "display_name": "Detach job files",
        "description": "Detach files from accessible jobs",
        "group": "jobs",
        "default_roles": ["super-admin", "admin", "user"],
        "version": 1,
        "deprecated": false
    },
    {
        "id": "unique-uuid-6",
        "name": "view-job-files",
        "display_name": "View job files",
        "description": "View and download files linked to accessible jobs",
        "group": "jobs",
        "default_roles": ["super-admin", "admin", "user"],
        "version": 1,
        "deprecated": false
    }
]
```

#### Step 3: Add Gates to Entity Enum

Create or update your entity's `Enums/JobGate.php`:

```php
enum JobGate: string
{
    // ... other gates
    case LIST_JOB_FILES = 'list-job-files-gate';
    case ATTACH_ANY_JOB_FILES = 'attach-any-job-files-gate';
    case ATTACH_JOB_FILES = 'attach-job-files-gate';
    case DETACH_JOB_FILES = 'detach-job-files-gate';
    case DETACH_ANY_JOB_FILES = 'detach-any-job-files-gate';
    case VIEW_JOB_FILES = 'view-job-files-gate';
}
```

#### Step 4: Add Policy Methods

Update your entity's `Policies/JobPolicy.php`:

```php
public function listFiles(User $user, Job $entity): bool
{
    return $user->hasPermissionTo('view-any-file') 
        || ($user->hasPermissionTo("list-job-files") && $this->canAccessJob($user, $entity));
}

public function attachAny(User $user): bool
{
    return $user->hasPermissionTo("attach-any-job-files");
}

public function attach(User $user, Job $entity): bool
{
    return $user->hasPermissionTo("attach-any-job-files") 
        || ($user->hasPermissionTo("attach-job-files") && $this->canAccessJob($user, $entity));
}

public function detach(User $user, Job $entity): bool
{
    return $user->hasPermissionTo("detach-any-job-files") 
        || ($user->hasPermissionTo("detach-job-files") && $this->canAccessJob($user, $entity));
}

public function detachAny(User $user): bool
{
    return $user->hasPermissionTo("detach-any-job-files");
}
```

#### Step 5: Register Gates in Service Provider

Update your entity's `Providers/JobServiceProvider.php`:

```php
public function boot(): void
{
    // ... other gates
    Gate::define(JobGate::LIST_JOB_FILES, [JobPolicy::class, 'listFiles']);
    Gate::define(JobGate::ATTACH_ANY_JOB_FILES, [JobPolicy::class, 'attachAny']);
    Gate::define(JobGate::ATTACH_JOB_FILES, [JobPolicy::class, 'attach']);
    Gate::define(JobGate::DETACH_JOB_FILES, [JobPolicy::class, 'detach']);
    Gate::define(JobGate::DETACH_ANY_JOB_FILES, [JobPolicy::class, 'detachAny']);
}
```

#### Step 6: Update File Policy View Method

The File Policy's `view` method automatically checks entity-specific permissions:

```php
public function view(User $user, File $file, ?Model $entity = null): bool
{
    return $user->hasPermissionTo("download-any-file")
        || ($entity &&
            $file->isLinkedTo($entity) &&
            $user->can("view-" . $entity->getMorphClass() . "-files", $entity));
}
```

This will automatically look for permissions like `view-job-files`, `view-user-files`, etc.

### Permission Naming Convention

Follow this pattern for file permissions:

- **List**: `list-{entity}-files`
- **Attach (Any)**: `attach-any-{entity}-files`
- **Attach (Own)**: `attach-{entity}-files`
- **Detach (Any)**: `detach-any-{entity}-files`
- **Detach (Own)**: `detach-{entity}-files`
- **View**: `view-{entity}-files`

---

## Audit Trail

The File Container implements comprehensive audit logging using the **Laravel Auditing** package.

### Configuration

**File Model** (`Models/File.php`):

```php
use OwenIt\Auditing\Contracts\Auditable;

final class File extends ParentModel implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    
    // ... model definition
}
```

### Audited Operations

All file operations are automatically logged, including:

1. **File Creation**: When a file is uploaded
2. **File Updates**: When file metadata is modified (description, tags, visibility)
3. **File Deletion**: When a file is soft-deleted or permanently deleted

### Audit Data Captured

For each operation, the audit trail captures:

- **User**: Who performed the action
- **Event**: Type of operation (created, updated, deleted)
- **Old Values**: Previous state of the file
- **New Values**: New state of the file
- **IP Address**: User's IP address
- **User Agent**: Browser/client information
- **URL**: API endpoint used
- **Timestamp**: When the action occurred

### Accessing Audit Logs

```php
// Get all audits for a file
$file = File::find($fileId);
$audits = $file->audits;

// Get latest audit
$latestAudit = $file->audits()->latest()->first();

// Filter audits by event
$creationAudit = $file->audits()->where('event', 'created')->first();
```

### FileLink Model

Note: The `FileLink` model (polymorphic pivot table) does **not** have audit trail enabled as it's a simple relationship table. However, the attachment/detachment actions are logged through the application's `Applog` function in the Actions layer.

---

## Architecture

### Directory Structure

```
File/
├── Actions/              # Business logic
│   ├── AttachFileAction.php
│   ├── CreateFileAction.php
│   ├── DeleteFileAction.php
│   ├── DetachFileAction.php
│   ├── FindFileByIdAction.php
│   ├── ListEntityFilesAction.php
│   ├── ListFilesAction.php
│   └── UpdateFileAction.php
├── API/
│   ├── Controllers/
│   │   ├── FileCRUDController.php
│   │   └── FileLinkController.php
│   ├── Requests/       # Form request validation
│   ├── Routes/         # API routes
│   └── Transformers/   # API response transformers
├── Configs/            # Container configuration
├── Data/
│   ├── DTOs/          # Data Transfer Objects
│   ├── Factories/     # Model factories
│   ├── Migrations/    # Database migrations
│   └── Repositories/  # Data access layer
├── Enums/             # File-related enums and gates
├── Models/
│   ├── File.php       # Main file model (with audit)
│   └── FileLink.php   # Polymorphic relationship model
├── Policies/          # Authorization policies
├── Providers/         # Service providers
├── Tasks/             # Reusable tasks
├── Tests/             # Unit and functional tests
└── permissions.json   # Permission definitions
```

### Models

#### File Model

- Soft deletes enabled
- UUID primary key
- Implements Auditable interface
- Supports tags (JSON column)
- Visibility options: public, private, share_link

#### FileLink Model

- Polymorphic pivot table
- Links files to any entity
- No timestamps (simple relationship)

---

## Testing

The File Container includes comprehensive tests:

### Unit Tests

- **Actions**: Test business logic in isolation
- **Tasks**: Test individual tasks
- **Requests**: Test validation rules

### Functional Tests

- **API**: End-to-end testing of all API endpoints
- Authorization tests
- Validation tests
- Error handling tests

### Running Tests

```bash
# Run all File container tests
docker exec laravel_app php artisan test app/Containers/AppSection/File/
```
---
