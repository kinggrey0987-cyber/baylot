# System Features & User Access Guide

**Last Updated:** March 11, 2026

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Core Features](#core-features)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Access Levels by Role](#access-levels-by-role)

---

## System Overview

This is a **Real Estate Management System** designed to manage property listings, leads, appointments, and agent activities in the Negros Occidental region. The system supports multiple user roles with distinct capabilities and access controls.

---

## Core Features

### 1. **Property Management (Listings)**
- Create, edit, and delete property listings
- Support for multiple property types and listing types (exclusive, shared)
- Track property details including:
  - Price and price ranges
  - Lot and floor area measurements
  - Bedroom/bathroom counts (with ranges)
  - Number of floors
  - Availability status
- Assign listings to agents
- Support for multiple units in a single listing

### 2. **Projects & Developments**
- Create and manage real estate projects
- Assign agents to specific projects
- Track project descriptions and metadata
- Link multiple listings to projects
- Geofencing capabilities for location-based services

### 3. **Lead Management**
- Generate leads from property inquiries
- Lead status tracking (active, claimed, assigned)
- Lead routing and claim system for agents
- Message tracking with leads
- Shared lead notifications

### 4. **Appointment Scheduling**
- Schedule property viewing appointments
- Record appointment details (start/end times)
- Appointment status management (scheduled, completed, cancelled)
- Agent notes on appointments
- Track down payment status
- Confirm sales through appointments
- Buyer-initiated appointment cancellation

### 5. **Lot Management**
- Create and manage individual lots within listings
- Support for lot-specific details

### 6. **Authentication & Security**
- User registration and login
- Two-Factor Authentication (2FA) via OTP email
- Email verification requirement
- Login OTP with:
  - 10-minute expiry
  - Maximum 5 attempt limit
  - 60-second resend cooldown
  - Maximum 5 resends per hour
- Role-based access control (RBAC)

### 7. **User Profile Management**
- Edit user profile information
- Update account settings
- Account deletion capability

### 8. **Notifications**
- Shared lead notifications to relevant parties
- In-app notification system for agents
- Read/unread notification management
- Notification history

### 9. **Reporting & Analytics**
- System-level reports for administrators
- Performance tracking and analytics dashboard

### 10. **Geofencing & Location Services**
- Define geographic boundaries (GeoPSQL with PostGIS)
- Validate listings within specific geographic regions
- Location-based rules and validation

---

## User Roles & Permissions

### Role Hierarchy

| Role | Description | Access Level |
|------|-------------|--------------|
| **Admin** | System administrator with full control | Highest |
| **Broker** | Broker/agency with agent management capabilities | High |
| **Agent** | Real estate agent managing listings and leads | Medium |
| **Buyer** | Prospective buyer viewing listings and scheduling | Basic |

---

## Access Levels by Role

### 🔐 **ADMIN** (Administrator)

**Authentication Requirements:**
- ✅ Logged in
- ✅ 2FA verified
- ✅ Email verified

**Dashboard:** Admin Dashboard

**Permissions:**

#### Agent Management
- View all agents
- Create new agents
- Toggle agent status (active/inactive)
- View agent performance

#### Project Management
- View all projects
- Create new projects
- Edit existing projects
- Delete projects
- Assign agents to projects

#### Listing Management (Shared Listings)
- View all shared listings
- Create shared listings
- Edit shared listings
- Delete shared listings

#### Reports & Analytics
- Access admin reports
- View system-wide analytics

#### Other Capabilities
- Profile management (edit/delete)
- Access all admin routes (`/admin/*`)

---

### 💼 **AGENT & BROKER** (Agent/Real Estate Professional)

**Authentication Requirements:**
- ✅ Logged in
- ✅ 2FA verified
- ✅ Email verified

**Dashboard:** Agent Dashboard

**Permissions:**

#### Listing Management
- ✅ View own listings
- ✅ Create new listings
- ✅ Edit own exclusive listings
- ✅ Delete own exclusive listings
- ✅ Create lots for listings
- ✅ Cannot edit shared listings (view-only)

**Listing Edit Policy:**
- Can only edit listings with `listing_type = 'exclusive'`
- Can only edit listings where `owner_agent_id` matches current user

#### Lead Management
- View lead queue
- Claim available leads
- View claimed leads
- Receive notifications when leads are shared

#### Appointment Management
- ✅ View all appointments
- ✅ Update appointment status
- ✅ Add/edit agent notes
- ✅ Record down payment received
- ✅ Confirm sales

#### Notifications
- View notifications
- Mark notifications as read
- Mark all notifications as read

#### Profile Management
- Edit own profile
- Delete own account

#### Access Routes
- All routes under `/agent/*` prefix

**Role Distinction:**
- `Broker` role: Identified with `in_array($role, ['agent', 'broker'])`
- `Agent` role: Treated same as broker in system access

---

### 👤 **BUYER** (Property Buyer/Lead)

**Authentication Requirements:**
- ✅ Logged in
- ✅ 2FA verified
- ✅ Email verified

**Dashboard:** Buyer Dashboard

**Permissions:**

#### Listing Browsing
- ✅ View all available listings
- ✅ View listing details
- ✅ Request property visits
- ✅ Inquire about properties

#### Appointment Management
- ✅ View scheduled appointments
- ✅ Cancel scheduled appointments
- ✅ Cancel appointments before scheduled time

#### Profile Management
- Edit own profile
- Delete own account

#### Access Routes
- Limited to `/buyer/*` routes
- View public listings

---

## Feature Access Matrix

| Feature | Admin | Agent/Broker | Buyer |
|---------|-------|--------------|-------|
| Create Listings | ❌ (delegates to agents) | ✅ (exclusive only) | ❌ |
| Edit Listings | ❌ (via admin interface) | ✅ (own, exclusive) | ❌ |
| Delete Listings | ❌ (via admin interface) | ✅ (own, exclusive) | ❌ |
| Browse Listings | ✅ | ✅ | ✅ |
| Manage Projects | ✅ | ❌ | ❌ |
| View Leads | ❌ | ✅ | N/A |
| Claim Leads | ❌ | ✅ | N/A |
| Schedule Appointments | ❌ | ✅ | ✅ (request) |
| Manage Appointments | ✅ (indirectly) | ✅ | ✅ (cancel) |
| View Reports | ✅ | ❌ | ❌ |
| Manage Agents | ✅ | ❌ | ❌ |
| View Notifications | ✅ | ✅ | ❌ |

---

## Key Security Features

### Authentication
- Email-based login with password
- Two-factor authentication (OTP via email)
- Email verification required
- Session management after 2FA verification

### Authorization
- Role-based access control (RBAC)
- Policy-based authorization for resources
- Route middleware enforcement (`role:admin`, `role:agent,broker`, `role:buyer`)
- Middleware ensures user is active before granting access

### Data Privacy
- Users can only access/modify their own data (except admin)
- Listing editing restricted to owners/admins only
- Appointment records tied to specific agents and buyers

---

## System Status & Operations

### Listing Status Types
- `active` - Available for viewing
- `inactive` - Hidden from search
- `sold` - Completed transaction

### Lead Status Types
- `active` - Unassigned lead
- `claimed` - Claimed by agent
- `assigned` - Assigned to specific agent

### Appointment Status Types
- `scheduled` - Confirmed appointment
- `completed` - Appointment attended
- `cancelled` - Cancelled by buyer or agent

---

## Geographic Features

### PostGIS Integration
- Geographic data storage and querying
- Geofence boundaries for location-based features
- Validation rules:
  - `InsideNegrosOccidentalGeometryRule` - Complex geometry validation
  - `InsideNegrosOccidentalPointRule` - Point-based location validation

### Working Hours Validation
- `WithinWorkingHours` - Ensures appointments scheduled within business hours

---

## Database Models

### Core Entities
1. **User** - System users (admin, agent, broker, buyer)
2. **Listing** - Property listings with details
3. **Project** - Real estate development projects
4. **Lead** - Property inquiries from buyers
5. **Appointment** - Scheduled property viewings
6. **Lot** - Individual units within projects
7. **Geofence** - Geographic boundary definitions
8. **LoginOtp** - Two-factor authentication records
9. **ListingImage** - Property photos
10. **LeadClaim** - Lead assignment records

---

## Additional Capabilities

### Email Notifications
- Login OTP delivery
- New shared lead notifications
- Appointment reminders/updates

### Multi-language Support
- Falkirk PHP translation system integrated

### Response/Request Validation
- Comprehensive form validation
- Custom validation rules
- Request-based authorization

---

## Notes

- All date/time fields use UTC timezone internally
- Prices stored as decimal(18,2) for accuracy
- All primary keys are UUID v4 strings
- System enforces active user status for access
- Geographic features require PostGIS extension

---

For detailed implementation information, refer to the respective controller, model, and policy files in the `app/` directory.
