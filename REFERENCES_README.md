# SIPB References Guide

This repository maintains two bibliography files in different citation formats:

## 1. `BIBLIOGRAPHY.md` (IEEE Numbered Style)
- **Format**: IEEE Citation Style with numbered references [1], [2], etc.
- **Best for**: Formal documentation, thesis-style citations, academic contexts
- **Use when**: Writing documentation that needs numbered citations or when cross-referencing from formal papers

**Example citation:**
```
"SIPB implements role-based access control using Spatie Permission [12]."
```

## 2. `DOCUMENTATION_REFERENCES.md` (Author-Date Style)
- **Format**: Author-Date citations with inline relevance notes
- **Best for**: Developer quick reference, code comments, technical discussions
- **Use when**: Justifying code patterns, explaining architectural decisions, or developer onboarding

**Example citation:**
```
// Sanctum Docs (Laravel 11.x): https://laravel.com/docs/11.x/sanctum
// Stateless Bearer token protection across `/api/v1`
```

---

## Content Mapping

Both files contain the same verified references, organized differently:

| Domain | IEEE Numbers | Author-Date Section |
|--------|--------------|---------------------|
| Inventory & Stock | [1]–[3] | Inventory & Stock Management |
| Sanctum & RBAC | [4]–[6] | RBAC (Spatie) & Authentication (Sanctum) |
| Reporting & Exports | [7]–[9] | Reporting, Exports & Decision Support |
| Framework Docs | [10]–[14] | Multiple sections (Sanctum, Spatie, Excel, etc.) |
| REST API & Validation | [15]–[18] | Data Model & Keys, REST patterns |
| Architecture & Testing | [19]–[22] | Service-Layer Architecture, Testing |
| Security Standards | [23]–[25] | RBAC & Security section |

---

## DOI Verification Status

✅ **All journal DOIs verified** (as of 2025-11-11):
- [1]–[9]: Inventory, reporting, and management systems
- [19]–[22]: Architecture and multi-role patterns
- [4]–[6]: Sanctum and RBAC implementations

✅ **All official docs URLs checked** (as of 2025-11-11):
- Laravel 11.x, Sanctum, Spatie Permission v6
- Laravel-Excel, PHPUnit
- OWASP API Security, OAuth 2.0, JWT RFCs

---

## Usage Examples

### In Code Comments
```php
// ✅ Security: Use Sanctum token pruning to remove expired tokens
// Reference: Sanctum Docs (Laravel 11.x) - Token abilities and pruning
// https://laravel.com/docs/11.x/sanctum
$schedule->command('sanctum:prune-expired --hours=24')->daily();
```

### In Technical Documentation (README.md, AGENT.md)
```markdown
SIPB follows OWASP API Security Top 10 guidelines [25] with additional
protection layers including Sanctum token-based authentication [11] and
Spatie Permission RBAC [12].
```

### In API Documentation (dokumentasi-api.md)
```markdown
All endpoints follow RESTful architecture principles [15] with consistent
JSON responses wrapped via Laravel API Resources [16].
```

---

## Maintenance Notes

- Both files should be updated together when adding new references
- DOI verification should be re-run annually or when adding new journal citations
- Official documentation URLs should be checked when Laravel/Spatie major versions upgrade
- File last synchronized: **11 November 2025**

---

## Quick Reference Card

| Need | Use File | Format |
|------|----------|--------|
| Formal citation in thesis/paper | `BIBLIOGRAPHY.md` | [1] |
| Code comment justification | `DOCUMENTATION_REFERENCES.md` | Author-Date + URL |
| Technical documentation | Either (prefer IEEE) | [1] or Author-Date |
| Developer onboarding | `DOCUMENTATION_REFERENCES.md` | Domain-mapped sections |
| Academic compliance | `BIBLIOGRAPHY.md` | IEEE numbered |

---

**Maintained by**: SIPB Development Team  
**Last Updated**: 11 November 2025
