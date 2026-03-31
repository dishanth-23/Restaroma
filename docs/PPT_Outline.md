## PowerPoint Presentation Outline (Restaurant System)

### Slide 1 — Title
- **Restaurant System (Restaroma)**
- Team members, date, course/module (if applicable)

### Slide 2 — Problem Statement
- Traditional ordering/reservations are slow and manual
- Need centralized menu, ordering, promotions, and reservations management

### Slide 3 — Objectives
- Online menu browsing
- Fast order placement + receipt
- Reservation requests + admin approval
- Admin control over menu, orders, promos

### Slide 4 — Scope (In/Out)
- **In scope**: registration/login, menu, orders, payment (cash/card), promo codes, reservations, admin management
- **Out of scope** (typical for this build): delivery tracking, real payment gateway integration, multi-branch support

### Slide 5 — Users & Roles
- **Customer**: browse menu, order, pay, apply promo, reserve table, view receipt
- **Admin**: manage menu, orders, reservations, promotions

### Slide 6 — System Architecture (Diagram)
- Insert: `docs/diagrams-out/architecture.png`

### Slide 7 — Use Case Diagram
- Insert: `docs/diagrams-out/usecase.png`

### Slide 8 — Database Design (ERD)
- Insert: `docs/diagrams-out/erd.png`

### Slide 9 — DFD (Level 0)
- Insert: `docs/diagrams-out/dfd_level0.png`

### Slide 10 — Key Flow: Order + Payment (Sequence)
- Insert: `docs/diagrams-out/sequence_order_payment.png`
- Explain: order created → payment confirms totals → card decrements inventory → receipt generated

### Slide 11 — Functional Requirements Summary
- Auth (user/admin)
- Menu browsing/filtering
- Orders + payment + promo
- Reservations
- Admin management modules

### Slide 12 — Non-Functional Requirements
- Security (password hashing, CSRF, session access control)
- Data integrity (transactions)
- Usability (validation, printable receipt)

### Slide 13 — Conclusion / Demo Plan
- Short demo path:
  - Register → login → menu → order → payment → receipt
  - Reservation → admin approves
  - Admin adds a promo code and show discount applied

### Slide 14 — Q&A

