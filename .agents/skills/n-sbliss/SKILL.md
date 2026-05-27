---
name: n-sbliss
description:# N'S Bliss Lounge — Agent Skill

## Description
Use this skill for every task on the N'S Bliss Lounge 
restaurant system. Load it before reading any file.

## What This Project Is
Plain PHP + MySQL restaurant system. No framework.
Bootstrap 5 UI. jQuery + AJAX for interactions.
N'S Bliss Lounge — KK504 ST, Kigali, Rwanda.
Currency: RWF. No decimals in display.

## Absolute Rules — Never Break These
- DO NOT change anything not explicitly requested
- DO NOT redesign, refactor, or improve anything
- DO NOT change layout, styles, CSS, or workflow
- Touch only the minimum files needed for the task
- Client receipt format must never be modified
- All print views use window.print() only — no libraries
- Max 48 chars per line in print views (80mm thermal)
- Read the actual file before modifying it — never assume

## Key Files
- config/config.php → SITE_NAME, BASE_URL, CURRENCY (RWF)
- includes/Database.php → singleton, use getInstance()
- includes/TableManager.php → table session management
- includes/session_check.php → validates session
- table_login.php → cashier selects waiter only (no table)
- menu.php → customer ordering interface (353 lines)
- api/orders/create.php → order submission endpoint
- assets/js/main.js → cart and checkout JS logic
- views/print/ → print ticket templates
- uploads/menu/ → menu item images

## Session Variables
- $_SESSION['waiter_id'] — selected waiter ID
- $_SESSION['waiter_name'] — selected waiter name
- NO table_number in session (removed)

## Database Key Tables
- categories (id, name, parent_id, is_active)
- menu_items (id, name, category_id, price, 
  description, image_path, is_active)
- orders (id, waiter_id, waiter_name, total, 
  created_at — read actual schema for full columns)
- order_items (order_id, menu_item_id, quantity, price)

## Changes Made From Original
1. table_login.php: waiter dropdown only, no table number
2. Printing: 3 separate tickets by category
   - coffee_ticket.php → coffee category items only
   - juice_ticket.php → juice category items only  
   - kitchen_ticket.php → food items only
   - Client receipt: UNCHANGED
3. api/orders/create.php: saves waiter_id from session
4. Electron desktop wrapper: PHP built-in server approach

## Print Ticket Rules
- Only print ticket if that category has items in order
- Kitchen ticket: food only, no prices, large font
- Coffee ticket: coffee items only, no prices
- Juice ticket: juice items only, no prices
- Each ticket auto-prints via window.print()
- Each opens in a new window after order submit

## Desktop App Approach
- Electron + bundled PHP binary in /php/ folder
- MySQL must run locally (XAMPP)
- Build output: dist/NBliss-Setup.exe
- PHP spawned as child process on random local port

## Tasks Status (update after each task)
- [ ] Task 1: Database schema confirmed
- [ ] Task 2: Waiter selection on table_login.php
- [ ] Task 3: Split printing by category
- [ ] Task 4: Client drinks tab confirmed unchanged
- [ ] Task 5: Desktop app conversion
- [ ] Task 6: CLAUDE.md created

## Recovery Instructions
If session was interrupted:
1. Read CLAUDE.md first
2. Check tasks status above
3. Find broken/incomplete files
4. Fix silently then report
5. Continue from last incomplete task
6. Never redo completed tasks

## Commit Pattern
After each task:
git add . && git commit -m "task [N]: [description]"
git push
---

<!-- Tip: Use /create-skill in chat to generate content with agent assistance -->

Define the functionality provided by this skill, including detailed instructions and examples