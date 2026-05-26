# Marcel — Early Risk Prediction Agent

This is the code for my BSc (Hons) Computing top-up dissertation. Module CMP600.

The project is a web app that takes a CSV of student data (attendance, marks, engagement, optionally tutor comments) and flags students who look like they might be heading for trouble. It's meant to be usable by a tutor with no technical background — upload a file, click through, get a dashboard with explanations next to each flag.

Live version: https://marcel.deansygrove.co.uk
There's a "Load Sample Data" button on the front page so you don't have to sign in to have a look.

## Why I built it

The dissertation question was basically: can you build a usable early warning system for higher education without writing any code? Spoiler: not really, at least not the whole thing. But you can build something that *feels* no-code to the tutor, and that's what's actually useful in practice.

The four sub-questions I tried to answer:

- What signals are most predictive of students being at risk?
- How would you design something like this with no-code tools?
- Is the result usable, accurate, and practical for institutions?
- Where does pure no-code stop working?

The last one turned out to be the most interesting. I started with an n8n workflow doing everything, and it broke down in four specific places: variable CSV formats, interactive dashboards, PDF reports, and per-user state. So I pivoted to a hybrid setup. Coded React and PHP under the hood, no-code-feeling UX on top.

## What's in here

- `app/` — the React + TypeScript front-end (Vite, Tailwind, Chart.js, jsPDF). This is what the tutor actually uses.
- `register/` — the PHP back-end for sign-up, login, and saved reports. Plain MVC, no framework.
- `generate_data.py` — generator for the synthetic datasets. Five behavioural archetypes from the literature, four cohort sizes. Deterministic, so the evaluation is reproducible.
- `template/` — a blank CSV showing the schema an institution would upload.
- `Docs/` — module materials.

I evaluated against the synthetic data only. I never had access to real student outcomes, and I wouldn't have wanted to without a proper ethics process anyway. The dissertation is honest about that. Real-world accuracy would need a longitudinal pilot, which is the first item in the future-work list.

## Running it locally

Front-end:

```bash
cd app
npm install
npm run dev
```

That spins up Vite on `http://localhost:5173`. Drop in any CSV matching the template, or hit "Load Sample Data".

Synthetic data generator:

```bash
python3 generate_data.py
```

Outputs CSVs under `generated/`. They're regenerated fresh every time, so they're in `.gitignore` and not committed.

Back-end (`register/`) is a plain PHP app. Copy `register/config.example.php` to see which environment variables it expects (DB credentials, Gemini API key if you want sentiment analysis), set them in your hosting environment, and drop the folder on any LAMP host. I used 20i shared hosting.

## How the scoring works

I went with explicit, interpretable weights rather than a learned model. The literature was clear enough on which signals matter that hard-coded weights were defensible, and using them meant every flag could come with a plain-English explanation. That was the whole point.

Without the AI sentiment add-on:

- 35% attendance
- 35% assessment
- 30% engagement

With Gemini sentiment analysis turned on:

- 30% attendance
- 30% assessment
- 20% engagement
- 20% sentiment of tutor comments

Plus a trend penalty (for students whose numbers are sliding even when the absolute level is fine) and a streak bonus (for consistent performance). The trend bit matters more than I expected. Hu (2014) and a few others showed direction of travel is as informative as the level itself.

## Project management

The agile board with sprints, planning cards and retrospectives is here:
https://github.com/users/expertul76/projects/3

Four sprints:

1. Research and proposal
2. Risk engine design (the n8n prototype that didn't survive contact with reality)
3. Hybrid pivot and UI build
4. Evaluation and write-up

## Ethics

- Synthetic data only. No real student records were touched.
- The risk engine doesn't see demographics. I wouldn't trust myself to do that responsibly without a much bigger study.
- There's a "Teacher Review" control wherever a flag appears, so the tutor can override or annotate. Human in the loop, not human rubber-stamp.
- Every flag has an explanation. No black boxes.

## What I'd do differently

If I had another semester:

- Get real outcome data and learn the weights from it instead of hard-coding them.
- Externalise the engagement keyword list so institutions could adapt it.
- Write Moodle / Canvas / Blackboard connectors so the tutor doesn't have to upload anything manually.
- Add an equity audit that reports flag distribution across demographics without acting on them.

Next-version problems. What's here is what the dissertation submitted.

---

Marcel Bucur — student ID 2310-111665
