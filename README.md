# Advanced Search Module

## Overview
The Advanced Search Module is a custom module designed for DataLife Engine (DLE) CMS. This module provides enhanced search functionality, allowing users to perform searches across various content fields and categories, with options for sorting and filtering results.

## Features
- **Flexible Search Parameters:** Supports searching by keyword, category, and various content fields (title, short content, full content).
- **Sorting and Ordering:** Allows results to be sorted by title or release date, with options for ascending or descending order.
- **Date Filtering:** Enables filtering results based on release date, with validation for future dates.
- **Error Handling:** Comprehensive error handling for invalid input and API requests.
- **Easy Installation:** Simple installation process with automatic setup of required files and database.
- **API Access:** Requires a secret key to be generated in the admin panel for secure API access.

## API Parameters
The following parameters can be used when making a request to the API:

| Parameter          | Type                  | Available Values                                         | Example            | Default Value |
|--------------------|-----------------------|----------------------------------------------------------|--------------------|---------------|
| `keyword`          | array [string]        | N/A                                                      | `["keyword-1", "keyword-2"]` | `[]`          |
| `searchin`         | string                | `"all"`, `"title"`, `"shortcontent"`, `"fullcontent"`, `"extrafields"` | `"all"`            | `"all"`       |
| `extrafields`      | array [object]        | N/A                                                      | `[{"key-1": "val-1"}, {"key-2": "val-2"}]` | `null`        |
| `extrafieldMatch`  | string                | `"some"`, `"every"`                                      | `"every"`          | `"some"`      |
| `category`         | array [numbers]       | N/A                                                      | `[1, 5, 2]`        | `[]`          |
| `subcats`          | boolean               | `true`, `false`                                          | `true`             | `false`       |
| `sort`             | string                | `"title"`, `"relasedate"`, `"shortcontent"`              | `"title"`          | `null`        |
| `order`            | string                | `"asc"`, `"desc"`                                        | `"asc"`            | `"asc"`       |
| `relasedate`       | string                | N/A                                                      | `"2024-10-01"`     | `"all"`       |
| `relasedateDir`    | string                | `"up"`, `"down"`                                         | `"up"`             | `null`        |

### Example API Request
Here’s an example of how to construct a request to the API using the provided parameters:

```javascript
const apiUrl = 'https://domain_name.com/engine/ajax/advancedsearch.php';

const params = {
    "keyword": ["keyword-1", "keyword-2"],
    "searchin": "all",
    "category": [1, 5],
    "extrafields": [{"key-1": "val-1"}, {"key-2": "val-2"}],
    "extrafieldMatch": "every",
    "subcats": true,
    "sort": "title",
    "order": "asc",
    "relasedate": "2024-10-01",
    "relasedateDir": "up"
};

fetch(apiUrl, {
    method: 'POST',
    headers: {
        'Api-Key': 'YOUR_API_KEY_HERE',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(params)
})
.then(response => {
    if (!response.ok) {
        throw new Error('API request failed');
    }
    return response.json();
})
.then(data => {
    console.log('Search Results:', data);
})
.catch(error => console.error('Error:', error));
```

## Installation

1. **Upload Plugin:**
   - Users can upload the plugin through the DLE admin panel under the "Manage Plugins" section.
   - Upon successful upload, the following files will be automatically created in the specified directories:
     - `engine/ajax/advancedsearch.php`
     - `engine/modules/advancedsearch/functions.advancedsearch.php`
     - `engine/data/advancedsearch.php`
     - `engine/inc/advancedsearch.php`

2. **Generate Secret Key:**
   - To use the API, an admin must generate a secret key in the "Advanced Search" section of the admin panel.
   - This key will be used for authentication in API requests.

3. **Database Setup:**
   - The module will automatically create the required database table upon installation if necessary.

4. **Usage:**
   - The function for performing searches is accessible via an AJAX call to `advancedsearch.php` with the necessary parameters.
   - Example of a request payload:
     ```json
      {
        "keyword": ["keyword-1", "keyword-2"],
        "searchin": "all",
        "category": [1],
        "extrafields": [{"key-1": "val-1"}, {"key-2": "val-2"}],
        "extrafieldMatch": "every",
        "subcats": true,
        "sort": "title",
        "order": "asc",
        "relasedate": "2024-10-01",
        "relasedateDir": "up"
      }
     ```

## Requirements
- DataLife Engine (DLE) Version 17.0 or higher.

## Uninstallation
To remove the module, simply deactivate it from the DLE admin panel. This will remove any references from the admin panel and associated files.

**Warning:** Uninstalling the module may lead to the loss of any search-related configurations and settings. Ensure to back up any necessary data before proceeding.

## License
This module is proprietary and is intended for internal use within the company. Redistribution or use outside the company is prohibited.

## Author
**Ehmedli Ehmed** - Okmedia MMC
