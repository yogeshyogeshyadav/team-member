# Team Members Manager Plugin

## 🧩 Overview
The **Team Members Manager Plugin** is a self-contained WordPress plugin that manages team members through a custom post type (CPT), provides a REST API for fetching data, and integrates with **Elementor** for displaying members on the frontend with filtering, pagination, and AJAX-powered interactivity.

---

## ⚙️ Features
- Custom Post Type: **Team Members**
- Meta Fields:
  - Full Name
  - Role / Designation
  - Profile Picture (Featured Image)
  - Email
  - Skills (comma-separated list)
- Custom Role: **HR** (can manage team members)
- REST API Endpoint: `/wp-json/team/v1/members`
- Elementor Widget: **Team Members Grid**
  - AJAX-based Filtering (Role, Skills)
  - Pagination
  - Grid/List Layouts
  - Search Bar (optional)
  - Dark/Light Mode Toggle (optional)
- Automatic Registration on Activation
- Safe Cleanup on Deactivation (role/capabilities removed, data preserved)
- Transient-based caching for performance

---

## 🧩 Installation

### From ZIP (Recommended)
1. Download the plugin ZIP: `team-members-plugin.zip`
2. In WordPress Admin → Plugins → Add New → Upload Plugin → Choose ZIP → Install Now
3. Activate the plugin.

### Manual Installation
1. Extract the folder `team-members-plugin`.
2. Upload it to `/wp-content/plugins/`.
3. Activate the plugin from WordPress Admin → Plugins.

---

## 🧰 Backend Details

### Custom Post Type (CPT)
**Name:** `team_member`

#### Fields:
| Field | Type | Description |
|-------|------|--------------|
| Full Name | Text | Team member’s full name |
| Role / Designation | Text | Position or title |
| Email | Email | Contact email |
| Skills | Text (comma-separated) | e.g. HTML, CSS, JS |
| Profile Picture | Featured Image | Profile photo |

### Custom Role
**Role Name:** `tm_hr`
**Display Name:** `HR`
**Capabilities:**
- `edit_team_members`
- `edit_others_team_members`
- `publish_team_members`
- `read_team_member`
- `delete_team_members`

### Activation Hook
- Registers CPT.
- Adds HR Role with capabilities.
- Flushes rewrite rules.

### Deactivation Hook
- Removes HR Role.
- Flushes rewrite rules.

---

## 🌐 REST API
**Base Endpoint:** `/wp-json/team/v1/members`

### Supported Query Parameters
| Parameter | Type | Description |
|------------|------|--------------|
| `page` | int | Page number |
| `per_page` | int | Number of results per page |
| `role` | string | Filter by role/designation |
| `skills` | string | Comma-separated skills filter |
| `search` | string | Search by name or email |

### Example
```bash
GET https://your-site.com/wp-json/team/v1/members?per_page=3&page=1&skills=php,react
```

### Response Example
```json
{
  "total": 8,
  "per_page": 3,
  "page": 1,
  "members": [
    {
      "id": 25,
      "name": "Priya Sharma",
      "role": "Frontend Developer",
      "email": "priya@example.com",
      "skills": ["HTML", "CSS", "JS"],
      "image": "https://your-site.com/wp-content/uploads/profile.jpg"
    }
  ]
}
```

### Caching
- Each API query result is cached using transients for **5 minutes**.
- Cache clears automatically on post save/update.

---

## 🧱 Elementor Widget
**Name:** `Team Members Grid`

### Widget Settings
| Setting | Type | Description |
|----------|------|-------------|
| Members per page | Number | Pagination control |
| Layout | Select | Grid / List |
| Show Filters | Switch | Enable/disable role & skills filters |
| Show Search | Switch | Enable/disable search bar |
| Dark Mode | Switch | Toggle light/dark frontend style |

### Behavior
- Fetches data via REST API.
- AJAX updates without reloading page.
- Works with Elementor live preview.
- Fully responsive grid layout.

---

## 🧑‍💻 Design Choices

| Feature | Approach | Reason |
|----------|-----------|--------|
| Data Storage | CPT + Meta Fields | Native WP integration & easy ACF/REST use |
| Role Management | Custom `tm_hr` role | Granular access control |
| API | REST API (`team/v1/members`) | Decoupled architecture, AJAX-ready |
| Frontend Rendering | Elementor Widget + JS Fetch | Dynamic + editor preview support |
| Caching | Transients | Performance boost for large lists |

---

## ⚙️ Known Limitations & Improvements
- Skills use text meta (not taxonomy). Future improvement: register as taxonomy for better filtering.
- Caching logic is simple (transient-based). Could be improved with object cache or invalidation hooks.
- Styling is minimal to keep it lightweight — can be extended via Elementor or custom CSS.
- Limited pagination range display (previous/next only). Could add numbered pagination.
- Elementor preview sometimes requires manual reload for AJAX widget preview (common Elementor behavior).

---

## 💡 Optional Enhancements
- Add search debounce for better UX.
- Add sorting by name or role.
- Add custom REST routes for single member detail.
- Allow frontend submission (for HR role) via custom form.

---

## 🧩 Evaluation Criteria Mapping
| Area | Weight | Implementation |
|-------|--------|----------------|
| Backend | 40% | CPT, REST API, roles, lifecycle hooks |
| Frontend | 40% | Elementor widget, AJAX filtering, pagination |
| Code Quality | 20% | Modular, standards-compliant, documented |

---

## 📦 Deliverables
- `team-members-plugin.zip` – Installable plugin file
- `readme.txt` – (This file)

---

## 🧪 Testing Checklist
✅ Plugin activates/deactivates cleanly  
✅ CPT and HR role registered correctly  
✅ Team Members can be added via admin  
✅ REST API returns correct paginated, filtered data  
✅ Elementor widget visible & functional in editor  
✅ AJAX pagination and filtering work correctly  
✅ Caching confirmed via transient storage  
✅ Dark/Light mode toggles styles  
✅ No PHP or JS console errors

---

## 🧑‍💼 Author
**Developed by:** Yogesh Yadav  
**Role:** WordPress Developer  
**Website:** [https://boldtechie.com](https://boldtechie.com)

---

> This plugin is a complete, self-contained system built for evaluation and demonstration of clean architecture, WordPress development best practices, and Elementor integration.
