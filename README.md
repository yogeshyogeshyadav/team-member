/*
===========================================
🧩 TEAM MEMBERS MANAGER PLUGIN - README
===========================================

📦 Plugin Name: Team Members Manager
👨‍💻 Developer: Yogesh Yadav
🌐 Website: https://boldtechie.com
📅 Version: 1.0.0
📁 File: readme.txt
===========================================
*/

/*
-----------------------------------------------------
🧩 OVERVIEW
-----------------------------------------------------
This plugin allows WordPress admins to manage team members from the backend
and display them dynamically on the frontend using Elementor.

It automatically registers a Custom Post Type (CPT) named “Team Members” 
with custom fields, a REST API endpoint, a custom HR role, and an Elementor widget
to show team members with AJAX-based filters and pagination.
*/

/*
-----------------------------------------------------
⚙️ WHAT I BUILT
-----------------------------------------------------
1️⃣ Custom Post Type: `team_member`
   - Fields: Full Name, Role/Designation, Email, Skills, Profile Picture (featured image)

2️⃣ Custom Role: `HR`
   - Role Name: `tm_hr`
   - Capabilities to manage Team Members (add/edit/delete/publish)

3️⃣ REST API Endpoint:
   - URL: /wp-json/team/v1/members
   - Supports filtering by role, skills, and search keyword
   - Pagination and caching (via transients)

4️⃣ Elementor Widget: “Team Members Grid”
   - Displays all team members dynamically
   - AJAX filtering by role/skills
   - Pagination support
   - Optional search bar and dark/light mode toggle
   - Works inside Elementor live preview

5️⃣ Plugin Lifecycle:
   - On Activation → Registers CPT + HR role + flushes rewrites
   - On Deactivation → Removes HR role safely + flushes rewrites
*/

/*
-----------------------------------------------------
🧠 DESIGN DECISIONS
-----------------------------------------------------
| Feature        | Decision                           | Reason |
|----------------|------------------------------------|--------|
| Data Storage   | Custom Post Type + Meta Fields     | Native WordPress structure |
| User Role      | Custom `HR` role                   | Granular access control |
| API Layer      | Custom REST route `/team/v1/members` | Decoupled backend/frontend |
| Frontend       | Elementor Widget with AJAX Fetch   | Dynamic & easy to use |
| Caching        | Transients                         | Lightweight & improves speed |
*/

/*
-----------------------------------------------------
🧪 TESTING DONE
-----------------------------------------------------
✅ Plugin activates/deactivates without errors
✅ CPT and HR role registered correctly
✅ Team Members can be created and managed
✅ REST API works with pagination and filters
✅ Elementor widget fetches members dynamically
✅ AJAX filtering & pagination tested
✅ Caching verified (transient stored)
✅ Dark/Light mode switch works
✅ No PHP or JS console errors found
*/

/*
-----------------------------------------------------
🚀 DELIVERABLES
-----------------------------------------------------
📁 team-members-plugin.zip — Installable plugin file
📝 readme.txt — This explanation file
*/

/*
-----------------------------------------------------
👨‍💻 ABOUT DEVELOPER
-----------------------------------------------------
👤 Name: Yogesh Yadav
💼 Role: WordPress Developer
🌐 Website: https://boldtechie.com
📧 Purpose: Built for machine task submission (Team Members Management Plugin)
*/
