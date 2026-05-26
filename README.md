# Marcel — Early Risk Prediction Agent

**BSc (Hons) Computing [Top-up] — CMP600 Dissertation Artefact**
Author: Marcel Bucur (Student ID 2310-111665)
Module Tutor: Anjuna Deva Raj

> A no-code-experience Early Warning System (EWS) for Higher Education that flags students at risk of underachieving or dropping out, based on attendance, assessment performance, and engagement signals.

**Live demo:** https://marcel.deansygrove.co.uk
Demo login: `marcel@esl.ac.uk` / `Marcel2026!` (the *Load Sample Data* button works without login)

---

## Dissertation Context

### Research question

> *How feasible is it to create and implement a no-code early risk prediction agent that can successfully predict at-risk students?*

### Sub-questions

| # | Sub-question |
|---|--------------|
| a | What student information attributes are most useful for early risk prediction? |
| b | How can we design, develop, and automate an early warning system through no-code interfaces? |
| c | How usable, effective, and feasible is the prototype from the institution's perspective? |
| d | What difficulties are there in creating a predictive model with no-code? |

### Contribution

The dissertation makes two contributions to the no-code literature:

1. **The user-experience-layer / build-layer distinction** — the no-code claim is situated where the promise of accessibility actually resides (the UX), rather than at the build layer where technical compromises occur.
2. **A four-limit account of pure no-code** — variable inputs, interactive UI, rich document generation, and per-user persistent state are the four axes along which pure workflow-orchestration platforms (e.g. n8n) meet their match.

---

## Architecture

A **hybrid architecture** — pure no-code UX on top of a coded build layer:

```
                ┌──────────────────────────────────────────┐
   Tutor  ──>   │  React 19 + TypeScript + Vite frontend   │   <── No-code experience
   (upload      │   • Upload CSV  • Confirm mapping        │       (upload → click → view → save)
    CSV)        │   • Dashboard (Overview / Students /     │
                │     Export)  • Teacher Review            │
                └─────────────────┬────────────────────────┘
                                  │
                ┌─────────────────▼────────────────────────┐
                │  Risk Engine (TypeScript)                │   <── Coded build layer
                │   Attendance 35% · Assessment 35% ·       │       (literature-derived weights)
                │   Engagement 30%  (or 30/30/20/20 + AI)   │
                │   + trend penalty + streak bonus +        │
                │   plain-English explanation list          │
                └─────────────────┬────────────────────────┘
                                  │
                ┌─────────────────▼────────────────────────┐
                │  PHP registration & persistence layer    │
                │   (register/  — MVC, MySQL)              │
                └──────────────────────────────────────────┘
```

### Tech stack

| Layer | Tools |
|---|---|
| Frontend | React 19, TypeScript, Vite 8, Tailwind v4, Chart.js, jsPDF, PapaParse |
| Backend | PHP (custom MVC), MySQL |
| AI (optional) | Google Gemini (sentiment analysis of tutor comments) |
| Hosting | Commodity shared web hosting |

---

## Repository structure

```
.
├── app/                  React + TypeScript frontend (risk engine, dashboard, PDF/CSV export)
├── register/             PHP registration & persistence layer (MVC)
├── generated/            Synthetic student datasets (regenerable from generate_data.py)
├── template/             Real-world CSV template for institutional uploads
├── Docs/                 ESL module guide, brief, template, cover page
├── generate_data.py      Deterministic synthetic-data generator (five archetypes × four cohort sizes)
└── README.md             This file
```

---

## Getting started

### Frontend

```bash
cd app
npm install
npm run dev          # development server (http://localhost:5173)
npm run build        # production build into app/dist
```

### Synthetic-data generation

```bash
python3 generate_data.py
# Produces CSVs under generated/ for the five literature-inspired archetypes
```

### Backend (register/)

Deploy `register/` to any LAMP/LEMP host. Edit `register/config.php` for DB credentials.

---

## Risk Engine — Feature Weights

The engine is interpretable: every score is assigned a plain-English explanation list, with no hidden ML model.

| Feature | Weight (no AI) | Weight (with AI sentiment) | Source |
|---|---|---|---|
| Attendance | 35% | 30% | Hu (2014); Bañeres et al. (2020) |
| Assessment performance | 35% | 30% | Junejo et al. (2024); Romero & Ventura (2020) |
| Engagement | 30% | 20% | Viberg et al. (2018) |
| AI sentiment (tutor comments) | — | 20% | Sajja et al. (2023); Anghel et al. (2025) |

Plus an explicit **trend penalty** (direction-of-travel) and **streak bonus**, motivated by Hu (2014) and Zambrano, Lara & Romero (2024).

---

## Project Management & Version Control

This project is managed agile-style. The GitHub Project board for this repository contains the sprint plan, backlog, in-progress work, retrospectives, and milestones:

**Project board:** https://github.com/users/expertul76/projects/3

### Sprints

| Sprint | Focus | Milestone |
|---|---|---|
| Sprint 1 | Research & Proposal | M1 — Proposal approved |
| Sprint 2 | Design & Risk Engine | M2 — Risk engine working in n8n prototype |
| Sprint 3 | Hybrid Pivot & UI | M3 — Hybrid architecture deployed |
| Sprint 4 | Evaluation & Write-up | M4 — Evaluation complete · M5 — Submission |

The Trello/Jira-style board on GitHub Projects mirrors the agile management requirement of the CMP600 brief (Section *Project Planning and Design*).

---

## Evaluation

Evaluation is against **synthetic data only** (five literature-derived behavioural archetypes × four cohort sizes), per the dissertation's stated ethical position (Section 3.9). No claim of real-world predictive accuracy is made without longitudinal validation; this is named as the dissertation's primary future-work item (Section 7.5).

---

## Ethics

- Synthetic data only (no real student records)
- Demographic blindness in the risk engine
- Human-in-the-loop via the *Teacher Review* control
- Forced transparency of explanations (no black-box outputs)

---

## References

Full reference list is in the dissertation document. Key papers driving the design:

- Hu (2014) — *Attendance and behavioural data as leading indicators of risk*
- Viberg et al. (2018) — *Learning analytics deployment gap*
- Bañeres et al. (2020) — *Accelerating identification of students-at-risk*
- Romero & Ventura (2020) — *Educational data mining: prediction methods*
- Khaleghi Hozhabrasa (2025) — *No-code AI tools: capabilities and limits*
- Liwanag, Ebardo & Cheng (2025) — *Systematic review of no-code AI*
- Morales Tirado, Mulholland & Fernández (2024) — *Operationalisable AI in learning analytics*

---

## License

Academic artefact submitted for the BSc (Hons) Computing [Top-up] dissertation (CMP600). All rights reserved by the author unless otherwise stated.
