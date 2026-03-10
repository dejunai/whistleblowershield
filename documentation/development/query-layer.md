# Query Layer

## Overview

The query layer will eventually allow structured retrieval of legal information from the WhistleblowerShield knowledge base.

During early development, the focus remains on building a reliable legal data model rather than complex queries.

## Design Philosophy

Queries must be driven by legal structure rather than arbitrary keyword search.

Users should be able to discover laws through:

- jurisdiction
- agency
- program
- protected activity
- retaliation type

## Example Queries

Examples of meaningful legal queries include:

Which federal agencies administer whistleblower programs?

Which statutes protect financial industry whistleblowers?

Which laws provide monetary rewards for reporting fraud?

## Implementation Options

Potential future implementations include:

- WordPress WP_Query
- custom database indexes
- REST API endpoints
- external search engines

## Deferred Development

The query system will not be implemented until the legal dataset is sufficiently populated.

Early implementation risks premature optimization and schema instability.
