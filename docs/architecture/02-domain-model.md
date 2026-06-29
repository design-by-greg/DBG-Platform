# Domain Model v1.0

**Status:** Draft

## Core Entities

### Organisation

The primary entity of DBG Platform. It represents a company, club, association, public body or partner.

### Brand

A visual identity owned by an Organisation.

### Workspace

An internal work area used to group projects, assets, orders and workflows.

### Project

The main user-facing unit of work. A project represents an objective such as uniforms, event material, signage, onboarding or campaign production.

### Asset

Any reusable business object owned by an Organisation.

Examples:

- Logo
- Product
- BAT
- Document
- Image
- Template
- Color
- Font
- Campaign
- Vehicle marking
- Uniform

### Resource

A file attached to an Asset.

Examples:

- PDF
- SVG
- PNG
- AI
- EPS
- JPG

### Product Library Item

A validated product owned by an Organisation and available for reorder.

### View

A user-facing presentation of a subset of data. A store is a View, not a separate data model.

### Order

A commercial event linked to a Project and to one or more Assets.

### Production Job

The operational work required to produce an Order.

## Core Relation

Organisation -> Brand -> Workspace -> Project -> Assets -> Versions -> Orders -> Production Jobs

## Principle

Projects are the user's mental model. Orders are events. Assets are the long-term value.
