#!/usr/bin/env python3
"""
Marcel Project - Synthetic Student Data Generator
Generates 4 CSV files (30, 50, 75, 100 students) matching the template format.
Uses AI-generated feedback pools for realistic engagement notes and assessment feedback.
"""

import csv
import random
import json
import os
import string

# ============================================================
# CONFIGURATION
# ============================================================

OUTPUT_DIR = "/Volumes/Storage-2TB/Projects/Marcel/generated"
SIZES = [30, 50, 75, 100]

# CSV Header matching the template exactly
HEADER = [
    "First Name", "Last name", "Email Address", "Student ID", "Course ID", "Course Length (weeks)",
    "Week 1", "Engagement Notes", "Week 2", "Engagement Notes",
    "Week 3", "Engagement Notes", "Week 4", "Engagement Notes",
    "Week 5", "Engagement Notes", "Week 6", "Engagement Notes",
    "Week 7", "Engagement Notes", "Week 8", "Engagement Notes",
    "Week 9", "Engagement Notes", "Week 10", "Engagement Notes",
    "Week 11", "Engagement Notes", "Week 12", "Engagement Notes",
    "Week 13", "Engagement Notes", "Week 14", "Engagement Notes",
    "Week 15", "Engagement Notes", "Week 16", "Engagement Notes",
    "Formative Assessment 1 Uploaded", "Formative Assessment 1 Status",
    "Formative Assessment 1 Grade", "Detailed Assessment Feedback",
    "Formative Assessment 2 Uploaded", "Formative Assessment 2 Status",
    "Formative Assessment 2 Grade", "Detailed Assessment Feedback",
    "Summative Assessment 1 Uploaded", "Summative Assessment 1 Status",
    "Summative Assessment 1 Grade", "Detailed Assessment Feedback",
    "Summative Assessment 2 Uploaded", "Summative Assessment 2 Status",
    "Summative Assessment 2 Grade", "Detailed Assessment Feedback",
]

# ============================================================
# NAME POOLS
# ============================================================

FIRST_NAMES = [
    "Oliver", "Amelia", "George", "Isla", "Harry", "Ava", "Noah", "Mia",
    "Jack", "Isabella", "Leo", "Sophia", "Oscar", "Grace", "Charlie", "Lily",
    "Jacob", "Freya", "Thomas", "Emily", "James", "Poppy", "William", "Ella",
    "Henry", "Rosie", "Alexander", "Evie", "Daniel", "Florence", "Lucas", "Willow",
    "Ethan", "Sienna", "Archie", "Charlotte", "Joseph", "Daisy", "Samuel", "Alice",
    "Freddie", "Ivy", "Max", "Ruby", "Logan", "Matilda", "Isaac", "Harper",
    "Benjamin", "Luna", "Sebastian", "Elsie", "Edward", "Aria", "Adam", "Scarlett",
    "Alfie", "Millie", "Theodore", "Phoebe", "Arthur", "Layla", "Dylan", "Chloe",
    "Liam", "Penelope", "Elijah", "Eva", "Mason", "Violet", "Ryan", "Bella",
    "Aiden", "Abigail", "Owen", "Jasmine", "Nathan", "Esme", "Caleb", "Emilia",
    "Muhammad", "Fatima", "Yusuf", "Aisha", "Ibrahim", "Zara", "Ahmed", "Noor",
    "Priya", "Ravi", "Sana", "Arjun", "Mei", "Wei", "Yuki", "Kenji",
    "Olga", "Dimitri", "Elena", "Marco", "Sofia", "Carlos", "Ana", "Diego",
    "Pierre", "Marie", "Hans", "Ingrid", "Kofi", "Ama", "Tariq", "Leila",
    "Niamh", "Cian", "Aoife", "Sean", "Chiara", "Luca", "Bianca", "Matteo",
    "Petra", "Viktor", "Katarina", "Andrei", "Nadia", "Pavel", "Irina", "Sven",
]

LAST_NAMES = [
    "Smith", "Johnson", "Williams", "Brown", "Jones", "Garcia", "Miller", "Davis",
    "Rodriguez", "Martinez", "Hernandez", "Lopez", "Gonzalez", "Wilson", "Anderson",
    "Thomas", "Taylor", "Moore", "Jackson", "Martin", "Lee", "Perez", "Thompson",
    "White", "Harris", "Sanchez", "Clark", "Ramirez", "Lewis", "Robinson",
    "Walker", "Young", "Allen", "King", "Wright", "Scott", "Torres", "Nguyen",
    "Hill", "Flores", "Green", "Adams", "Nelson", "Baker", "Hall", "Rivera",
    "Campbell", "Mitchell", "Carter", "Roberts", "Patel", "Khan", "Singh", "Ahmed",
    "Ali", "Hassan", "Chen", "Wang", "Li", "Zhang", "Liu", "Yang",
    "Kim", "Park", "Tanaka", "Suzuki", "Nakamura", "Yamamoto", "Sato", "Ito",
    "Mueller", "Schmidt", "Fischer", "Weber", "Meyer", "Wagner", "Becker", "Schulz",
    "Rossi", "Russo", "Ferrari", "Esposito", "Romano", "Colombo", "Ricci", "Marino",
    "Dubois", "Laurent", "Moreau", "Bernard", "Petit", "Roux", "Leroy", "David",
    "O'Brien", "Murphy", "Kelly", "Walsh", "McCarthy", "Sullivan", "Byrne", "Ryan",
    "Kowalski", "Nowak", "Jansen", "De Vries", "Svensson", "Johansson", "Andersen",
    "Okafor", "Mensah", "Diallo", "Nkrumah", "Osei", "Adeyemi", "Chukwu", "Mwangi",
    "Fernandez", "Santos", "Oliveira", "Silva", "Costa", "Pereira", "Almeida", "Sousa",
]

# ============================================================
# COURSE CONFIGURATIONS
# ============================================================

COURSE_POOL = [
    {"id": "COMP101", "name": "Introduction to Computing", "length": 12},
    {"id": "BUS201", "name": "Business Management", "length": 16},
    {"id": "ENG105", "name": "Academic English", "length": 8},
    {"id": "MKT110", "name": "Digital Marketing", "length": 10},
    {"id": "HRM200", "name": "Human Resource Management", "length": 14},
    {"id": "FIN150", "name": "Financial Accounting", "length": 12},
    {"id": "LAW100", "name": "Introduction to Law", "length": 6},
    {"id": "PSY120", "name": "Psychology Foundations", "length": 10},
    {"id": "DES180", "name": "Graphic Design Principles", "length": 14},
    {"id": "DAT210", "name": "Data Analytics", "length": 16},
    {"id": "PRJ300", "name": "Project Management", "length": 8},
    {"id": "NET140", "name": "Networking Fundamentals", "length": 12},
]

def get_courses_for_size(size):
    """Determine courses and student distribution for a given file size."""
    if size == 30:
        courses = [
            {"id": "COMP101", "length": 12, "count": 16},
            {"id": "ENG105", "length": 8, "count": 14},
        ]
    elif size == 50:
        courses = [
            {"id": "BUS201", "length": 16, "count": 20},
            {"id": "MKT110", "length": 10, "count": 15},
            {"id": "LAW100", "length": 6, "count": 15},
        ]
    elif size == 75:
        courses = [
            {"id": "DAT210", "length": 16, "count": 22},
            {"id": "HRM200", "length": 14, "count": 20},
            {"id": "FIN150", "length": 12, "count": 18},
            {"id": "PRJ300", "length": 8, "count": 15},
        ]
    elif size == 100:
        courses = [
            {"id": "DES180", "length": 14, "count": 22},
            {"id": "NET140", "length": 12, "count": 20},
            {"id": "PSY120", "length": 10, "count": 20},
            {"id": "COMP101", "length": 12, "count": 20},
            {"id": "ENG105", "length": 8, "count": 18},
        ]
    return courses

def get_assessment_structure(course_length):
    """Determine which assessments are applicable based on course length."""
    if course_length <= 8:
        # Short course: 1 Summative only
        return {"formative1": False, "formative2": False, "summative1": True, "summative2": False}
    elif course_length <= 12:
        # Medium course: 1 Formative + 1 Summative
        return {"formative1": True, "formative2": False, "summative1": True, "summative2": False}
    else:
        # Long course: 2 Formative + 2 Summative
        return {"formative1": True, "formative2": True, "summative1": True, "summative2": True}

# ============================================================
# STUDENT ARCHETYPES
# ============================================================

ARCHETYPES = ["high_performer", "average", "struggling", "improving", "declining"]

# Probability weights for each archetype
ARCHETYPE_WEIGHTS = [0.20, 0.35, 0.20, 0.15, 0.10]

def pick_archetype():
    return random.choices(ARCHETYPES, weights=ARCHETYPE_WEIGHTS, k=1)[0]

# Attendance probability by archetype: {archetype: (present_prob, late_prob, absent_prob)}
ATTENDANCE_PROBS = {
    "high_performer": (0.85, 0.10, 0.05),
    "average":        (0.65, 0.15, 0.20),
    "struggling":     (0.40, 0.15, 0.45),
    "improving":      (0.60, 0.15, 0.25),  # Base, improves over time
    "declining":      (0.60, 0.15, 0.25),  # Base, worsens over time
}

def generate_attendance(archetype, course_length):
    """Generate weekly attendance for a student."""
    attendance = []
    for week in range(1, course_length + 1):
        probs = list(ATTENDANCE_PROBS[archetype])

        # Improving: attendance gets better over time
        if archetype == "improving":
            progress = week / course_length
            probs[0] = min(0.90, probs[0] + 0.25 * progress)  # More present
            probs[2] = max(0.05, probs[2] - 0.20 * progress)  # Less absent
            probs[1] = 1.0 - probs[0] - probs[2]

        # Declining: attendance worsens over time
        elif archetype == "declining":
            progress = week / course_length
            probs[0] = max(0.25, probs[0] - 0.30 * progress)  # Less present
            probs[2] = min(0.55, probs[2] + 0.25 * progress)  # More absent
            probs[1] = 1.0 - probs[0] - probs[2]

        state = random.choices(["Present", "Not Present", "Late"], weights=probs, k=1)[0]
        attendance.append(state)

    return attendance

# ============================================================
# FALLBACK FEEDBACK TEMPLATES
# ============================================================

FALLBACK_ENGAGEMENT = {
    "high_performer": {
        "Present": [
            "Excellent participation today. Contributed thoughtful points during the seminar discussion.",
            "Actively engaged throughout the session. Asked insightful questions during the Q&A.",
            "Strong contribution to group work. Demonstrated clear preparation for today's topic.",
            "Outstanding engagement. Took detailed notes and helped peers with understanding key concepts.",
            "Participated confidently in class discussion. Showed excellent grasp of the material.",
            "Led the group activity effectively. Demonstrated strong analytical thinking.",
            "Engaged well with the practical exercises. Completed all tasks ahead of schedule.",
            "Excellent focus throughout. Made valuable connections between theory and practice.",
            "Contributed meaningfully to peer discussions. Showed evidence of wider reading.",
            "Very well-prepared for today's session. Raised thought-provoking questions.",
        ],
        "Late": [
            "Arrived 10 minutes late but quickly caught up and contributed well to the session.",
            "Late arrival noted. Despite this, engaged productively once settled.",
            "Joined the session late. Please ensure timely attendance — missed the introduction to a key topic.",
            "Arrived after the register. Participation was strong once present, but punctuality needs attention.",
            "Late to class today. Caught up with group work quickly, but missed important context.",
            "Arrived late — second occurrence this term. Still contributed well once present.",
            "Punctuality issue today. Engaged fully after arrival but missed the initial briefing.",
            "Late arrival disrupted the start of group work. Strong contribution afterwards.",
        ],
        "Not Present": [
            "Absent from today's session. Unusual for this student — please check in.",
            "Not present today. Missed an important workshop activity — materials shared via email.",
            "Absent. This is uncharacteristic — will follow up to ensure everything is okay.",
            "Did not attend. Key assessment guidance was covered — student should review the recording.",
            "Not in class today. Peer group noted their absence during collaborative work.",
            "Absent from session. Important deadline reminders were discussed — follow-up email sent.",
        ],
    },
    "average": {
        "Present": [
            "Attended and followed along with the session content. Could contribute more to discussions.",
            "Present and engaged with the practical activities. Participation in group work was adequate.",
            "In attendance. Completed the set tasks but could push further with independent thinking.",
            "Present throughout. Engaged with materials but remained quiet during open discussion.",
            "Attended the session. Showed understanding of basic concepts but needs to develop analysis further.",
            "In class and working through activities. Would benefit from asking more questions.",
            "Present and attentive. Contributed briefly to group discussion — encourage more active participation.",
            "Attended the full session. Completed tasks at a satisfactory pace.",
            "Present. Took notes but limited verbal participation. Encourage engagement in seminars.",
            "In attendance. Adequate engagement with today's content.",
        ],
        "Late": [
            "Arrived late to the session. Participation was minimal once settled.",
            "Late arrival. Joined group work but struggled to catch up on the initial briefing.",
            "Arrived after the session started. Please work on punctuality — it affects learning.",
            "Late to class. Completed the activities but missed the theoretical introduction.",
            "Arrived late. Settled in quietly and engaged with the remaining tasks.",
            "Punctuality issue — arrived 15 minutes into the session. Adequate engagement afterwards.",
        ],
        "Not Present": [
            "Absent from today's session. Materials have been shared for independent study.",
            "Not present. Please review the session recording and complete the set activities.",
            "Did not attend. This is the second absence this term — monitor attendance pattern.",
            "Absent today. Missed group presentation preparation — will need to coordinate with peers.",
            "Not in class. Assessment preparation was discussed — recommend reviewing the slides.",
            "Absent. Engagement has been inconsistent — a catch-up meeting may be beneficial.",
        ],
    },
    "struggling": {
        "Present": [
            "Attended but appeared disengaged for much of the session. Encouraged to participate more actively.",
            "Present in class. Struggled with the practical exercises — offered additional support resources.",
            "In attendance but did not contribute to group discussion. Appeared uncertain about the material.",
            "Present. Found the activities challenging — recommended additional reading and tutorial support.",
            "Attended the session. Required significant guidance to complete the set tasks.",
            "In class but seemed overwhelmed by the content. Suggested booking a one-to-one tutorial.",
            "Present throughout. Minimal engagement with peers during collaborative work.",
            "Attended but did not complete the practical exercises. Needs to review foundational concepts.",
            "In class. Appeared confused during the lecture — followed up individually after the session.",
            "Present but passive. Would benefit from attending the additional support workshops.",
        ],
        "Late": [
            "Arrived late and appeared unprepared for the session. Engagement was minimal.",
            "Late arrival. Did not participate in the group activity. Support meeting recommended.",
            "Arrived after the session started. Seemed disoriented and did not complete the tasks.",
            "Late to class. Limited engagement once present — this is becoming a concerning pattern.",
            "Arrived late. Struggled to follow the remaining content. Extra support offered.",
        ],
        "Not Present": [
            "Absent from today's session. This is a recurring pattern — welfare check recommended.",
            "Not present. Multiple absences are impacting their ability to keep up with course content.",
            "Did not attend. Emailed student to check on wellbeing and offer support.",
            "Absent again. Falling behind on coursework — intervention meeting to be arranged.",
            "Not in class. Attendance is now below acceptable threshold — escalation may be needed.",
            "Absent. This student has missed key formative activities. Academic support referral made.",
        ],
    },
    "improving": {
        "Present": [
            "Good to see improved engagement today. Contributed to the group discussion for the first time.",
            "Noticeable improvement in participation. Asked a relevant question during the lecture.",
            "Present and more engaged than previous weeks. Completed the practical tasks independently.",
            "Encouraging progress. Showed better understanding of the material during today's activity.",
            "Attended and actively participated. A clear step up from earlier in the term.",
            "Present and focused. Took initiative in group work — positive trajectory.",
            "Good engagement today. Beginning to demonstrate understanding of core concepts.",
            "Attended and contributed. Growing confidence visible in class discussions.",
            "Improved focus and participation today. Keep up this momentum.",
            "Present and prepared. Noticeably better engagement compared to the start of term.",
        ],
        "Late": [
            "Arrived late but showed good engagement once settled. Punctuality still needs work.",
            "Late arrival, but participation was much improved once present. Encouraging progress.",
            "Arrived after the start. Despite lateness, contributed to the group task effectively.",
            "Late to class. Engagement after arrival was positive — keep working on punctuality.",
        ],
        "Not Present": [
            "Absent today. Disappointing given the recent improvements in engagement.",
            "Not present. Hope to see them back next week — had been making good progress.",
            "Did not attend. Important not to lose the momentum from recent positive sessions.",
            "Absent. Materials shared to help maintain the upward trajectory.",
        ],
    },
    "declining": {
        "Present": [
            "Present but noticeably less engaged than in previous weeks. Check in recommended.",
            "In attendance but participation has dropped compared to earlier in the term.",
            "Present. Did not contribute to discussion — a contrast from their earlier engagement.",
            "Attended but seemed distracted. Quality of in-class work has declined.",
            "Present throughout but passive. Previously one of the more engaged students.",
            "In class but did not complete the practical tasks. Declining focus is a concern.",
            "Present. Engagement has reduced significantly — one-to-one catch-up may be helpful.",
            "Attended the session. Appeared tired and unresponsive to group activities.",
        ],
        "Late": [
            "Arrived late — this is becoming more frequent. Engagement has also declined.",
            "Late to class. Previously punctual — the pattern is changing. Worth a check-in.",
            "Late arrival and minimal engagement. This is a noticeable shift from earlier weeks.",
            "Arrived after the start. Did not participate in the discussion. Concerning trend.",
        ],
        "Not Present": [
            "Absent from today's session. Attendance has been declining over recent weeks.",
            "Not present. This is the third absence recently — previously had good attendance.",
            "Did not attend. Combined with declining engagement, this warrants a welfare check.",
            "Absent again. Pattern of disengagement is escalating — intervention recommended.",
            "Not in class. Was previously a regular attendee — something may be affecting them.",
        ],
    },
}

FALLBACK_ASSESSMENT = {
    "high_completed": [
        "Excellent work demonstrating a thorough understanding of the key concepts. Critical analysis is well-developed, with effective use of academic references to support arguments. Structure and presentation are of a high standard.",
        "A strong submission that meets all learning outcomes effectively. The argument is clearly structured, with good use of evidence and critical evaluation. Minor improvements could be made to the conclusion.",
        "Outstanding piece of work. Demonstrates deep engagement with the literature and applies theoretical frameworks confidently. Referencing is accurate and consistent throughout.",
        "Very well-written assessment with clear evidence of independent research. The analysis is insightful and well-supported by relevant sources. Excellent academic writing throughout.",
        "High-quality work that demonstrates strong analytical skills. The student has addressed all assessment criteria comprehensively. A few minor points could be expanded for even greater depth.",
        "Impressive submission showing sophisticated understanding of the subject matter. Arguments are well-constructed and supported by a wide range of academic sources.",
        "Excellent standard of work. The student demonstrates confident application of theory to practice. Referencing and academic integrity are exemplary.",
        "A thorough and well-researched assessment. Clear structure, strong critical analysis, and effective use of examples to illustrate key points. Well done.",
        "Demonstrates mastery of the subject area with nuanced analysis. Writing style is academic and professional. All learning outcomes clearly met.",
        "Superb effort. The depth of analysis and quality of argumentation set this work apart. Demonstrates genuine intellectual engagement with the material.",
    ],
    "high_late": [
        "The quality of work is very good, with strong analysis and well-supported arguments. However, this was submitted after the deadline. Late penalties have been applied as per the assessment policy.",
        "Excellent content demonstrating deep understanding of the subject. It is disappointing that this was submitted late, as it detracts from an otherwise outstanding piece of work. Late penalty applied.",
        "A strong assessment meeting all criteria effectively. The late submission is noted — punctuality with deadlines is essential for professional development. Grade adjusted for lateness.",
        "High-quality analysis and referencing throughout. The late submission has resulted in a grade penalty. Please ensure future assessments are submitted on time.",
        "Very good work with clear critical thinking and academic rigour. Submitted late — the adjusted grade reflects the lateness penalty. The actual quality of work is commendable.",
    ],
    "medium_completed": [
        "A satisfactory submission that addresses the main assessment criteria. The analysis could be deeper in places, and additional academic references would strengthen the arguments. Structure is adequate.",
        "Adequate work demonstrating a reasonable understanding of the core concepts. Some arguments lack sufficient evidence or critical evaluation. Consider engaging more with the recommended reading.",
        "This assessment meets the basic requirements but would benefit from more detailed analysis. Referencing is generally adequate though inconsistent in places. Structure could be improved.",
        "A competent piece of work that covers the key areas. The discussion remains largely descriptive — try to develop more critical analysis in future assessments. Adequate referencing.",
        "Satisfactory submission. The student demonstrates an understanding of the topic but needs to develop their arguments more fully. Some sections lack depth and supporting evidence.",
        "Reasonable work that addresses the question. The analysis is surface-level in places and would benefit from deeper engagement with academic literature. Adequate structure overall.",
        "A passing submission that meets the minimum criteria. Greater critical engagement with the material would improve the grade. Some referencing errors noted.",
        "Competent work showing foundational understanding. The student should aim to move beyond description toward analysis and evaluation in future submissions.",
        "This assessment demonstrates adequate knowledge of the subject. However, the arguments are not always well-supported. More thorough research and referencing would improve the quality.",
        "Satisfactory effort. Key concepts are identified but not always fully explored. Academic writing style needs development — avoid informal language.",
    ],
    "medium_late": [
        "Adequate work that addresses the core assessment criteria. Submitted after the deadline — late penalty has been applied. The analysis could be strengthened with more critical evaluation.",
        "A satisfactory submission, though late. The content demonstrates basic understanding but lacks depth in places. Please ensure timely submission of future assessments.",
        "Reasonable work with some good points made. The late submission has resulted in a grade adjustment. Additional referencing and deeper analysis would improve future work.",
        "Competent submission but received after the deadline. The discussion is adequate but could benefit from more engagement with the academic literature. Grade adjusted for lateness.",
        "The assessment meets basic criteria but was submitted late. Arguments are underdeveloped in places. Recommend using the academic writing support service.",
    ],
    "low_completed": [
        "This assessment does not adequately address the learning outcomes. The analysis is superficial and lacks supporting evidence. Significant improvement is needed — please seek academic support.",
        "The submission demonstrates limited understanding of the key concepts. Arguments are poorly structured and insufficiently supported by references. A tutorial session is recommended.",
        "This work falls below the expected standard. The response is largely descriptive with minimal critical analysis. Academic referencing is inadequate. Please attend the study skills workshop.",
        "The assessment does not meet the required criteria. There are significant gaps in understanding, and the writing lacks academic rigour. Support services have been recommended.",
        "A weak submission that requires substantial improvement. Key concepts are misunderstood or absent. The student should review the module materials and book a one-to-one session.",
        "This work demonstrates insufficient engagement with the course material. The analysis is absent in key sections and referencing is incomplete. Academic intervention recommended.",
        "Below the expected standard. The response does not adequately address the question set. There are fundamental errors in understanding that need to be addressed urgently.",
        "The assessment shows limited effort and understanding. Critical analysis is absent, and the submission does not meet the learning outcomes. Please seek support immediately.",
    ],
    "low_late": [
        "This assessment was submitted late and does not meet the required standard. The content shows limited understanding and the analysis is insufficient. Late penalty has been applied. Academic support strongly recommended.",
        "Submitted after the deadline with significant quality issues. The work lacks structure, adequate referencing, and critical analysis. Please book a meeting to discuss support options.",
        "Late submission with below-standard content. Key learning outcomes are not addressed. The combined impact of late penalties and low quality is reflected in the grade. Immediate support recommended.",
        "This work was received after the deadline and falls below expectations. Arguments are weak and unsupported. The student should urgently engage with academic support services.",
        "Late and below standard. The assessment demonstrates fundamental gaps in understanding. A support plan should be developed with the personal tutor.",
    ],
    "not_uploaded": [
        "Assessment not submitted. This is a serious academic concern. The student has been contacted to discuss the non-submission and signposted to support services. A mark of 0% has been recorded.",
        "No submission received by the deadline or after. The student must contact their personal tutor to discuss the implications and any possible extenuating circumstances.",
        "Assessment not uploaded. This will result in a fail for this component. The student is strongly encouraged to engage with academic support and discuss their circumstances.",
        "Non-submission recorded. Unless extenuating circumstances are approved, this will be recorded as 0%. The student should contact the module leader urgently.",
        "No assessment submitted. This is a significant concern for the student's overall progress. A meeting has been requested to discuss next steps and available support.",
        "Assessment not received. The student should be aware that non-submission affects their overall module grade. Welfare and academic support teams have been notified.",
    ],
    "formative_yes": [
        "Good engagement with the formative assessment. The submission demonstrates a solid foundation and readiness for the summative assessment. Keep building on this work.",
        "Formative submission meets expectations. The student is on track and should continue developing their ideas for the final assessment. Good evidence of understanding.",
        "A positive formative submission showing good preparation. Some areas could be developed further — use the feedback provided to strengthen the summative assessment.",
        "Well-structured formative work. The student demonstrates adequate understanding of the topic. Recommend refining the analysis for the summative submission.",
        "Good effort on the formative assessment. Clear engagement with the learning materials. Use this as a springboard for the summative — focus on deepening the critical analysis.",
        "Formative assessment completed to a good standard. The student shows potential for a strong summative submission if they continue to develop their arguments.",
        "Solid formative submission. Key concepts are addressed and the structure is clear. Encourage the student to incorporate wider reading into the summative assessment.",
    ],
    "formative_no": [
        "The formative submission does not meet expectations. Key concepts are missing or misunderstood. The student should use the feedback to significantly revise their approach before the summative.",
        "Formative assessment falls below the expected standard. Limited engagement with the topic and insufficient structure. A support session is recommended before the summative deadline.",
        "This formative submission indicates the student is not on track for the summative assessment. Fundamental issues with understanding need to be addressed urgently.",
        "Below expectations for the formative assessment. The student needs to engage more deeply with the course materials and develop their academic writing skills.",
        "Formative work does not demonstrate adequate preparation. The student should book a tutorial to review the assessment criteria and develop a stronger approach for the summative.",
        "Insufficient formative submission. The response is too brief and lacks critical engagement with the topic. Without improvement, the summative outcome is at risk.",
    ],
}

# ============================================================
# AI FEEDBACK LOADING
# ============================================================

def _extract_json_from_ai_response(filepath):
    """Extract JSON from an n8n AI webhook response file."""
    with open(filepath, "r") as f:
        raw = f.read()
    if not raw.strip():
        return None

    data = json.loads(raw)

    # n8n wraps ChatGPT responses in: {"content": [{"text": "..."}]}
    if isinstance(data, dict) and "content" in data:
        text = data["content"][0]["text"]
    elif isinstance(data, dict) and "response" in data:
        text = data["response"]
    elif isinstance(data, dict) and any(k.startswith(("high", "low", "medium")) for k in data):
        return data  # Already parsed
    else:
        text = raw

    if isinstance(text, dict):
        return text

    # Strip markdown code blocks if present
    text = text.strip()
    if text.startswith("```"):
        lines = text.split("\n")
        text = "\n".join(lines[1:-1] if lines[-1].strip() == "```" else lines[1:])
        text = text.strip()

    return json.loads(text)


def load_ai_feedback():
    """Try to load AI-generated feedback from files. Fall back to templates."""
    engagement = None
    assessment = None

    # Try loading AI engagement notes
    for path in ["/tmp/marcel_engagement_notes.json"]:
        try:
            engagement = _extract_json_from_ai_response(path)
            if engagement:
                print(f"  Loaded AI engagement notes: {len(engagement)} categories")
                break
        except Exception as e:
            print(f"  Could not parse AI engagement notes from {path}: {e}")

    # Try loading AI assessment feedback
    for path in ["/tmp/marcel_assessment_feedback_ai.json", "/tmp/marcel_assessment_feedback.json"]:
        try:
            assessment = _extract_json_from_ai_response(path)
            if assessment:
                print(f"  Loaded AI assessment feedback: {len(assessment)} categories")
                break
        except Exception as e:
            print(f"  Could not parse AI assessment feedback from {path}: {e}")

    return engagement, assessment


def get_engagement_note(archetype, attendance_state, ai_engagement):
    """Get an engagement note for a given archetype and attendance state."""
    # Map archetype names to key prefixes for AI-generated feedback
    archetype_map = {
        "high_performer": "high",
        "average": "average",
        "struggling": "struggling",
        "improving": "improving",
        "declining": "declining",
    }
    state_map = {
        "Present": "present",
        "Late": "late",
        "Not Present": "absent",
    }

    prefix = archetype_map[archetype]
    suffix = state_map[attendance_state]
    key = f"{prefix}_{suffix}"

    # Try AI-generated first
    if ai_engagement and key in ai_engagement:
        pool = ai_engagement[key]
        if isinstance(pool, list) and len(pool) > 0:
            return random.choice(pool)

    # Fall back to templates
    fallback = FALLBACK_ENGAGEMENT.get(archetype, {}).get(attendance_state, [])
    if fallback:
        return random.choice(fallback)

    return "Student attended the session."


def get_assessment_feedback(grade, status, is_formative, ai_assessment):
    """Get assessment feedback based on grade, status, and type."""
    if status == "Not Completed":
        key = "not_uploaded"
    elif is_formative:
        key = "formative_yes" if grade and int(grade) >= 50 else "formative_no"
    elif grade is None or grade == "":
        key = "not_uploaded"
    else:
        g = int(grade)
        late = "_late" if status == "Late" else "_completed"
        if g >= 70:
            key = f"high{late}"
        elif g >= 50:
            key = f"medium{late}"
        else:
            key = f"low{late}"

    # Try AI-generated first
    if ai_assessment and key in ai_assessment:
        pool = ai_assessment[key]
        if isinstance(pool, list) and len(pool) > 0:
            return random.choice(pool)

    # Fall back to templates
    fallback = FALLBACK_ASSESSMENT.get(key, [])
    if fallback:
        return random.choice(fallback)

    return "Please see the module leader for further feedback."


# ============================================================
# GRADE GENERATION
# ============================================================

def generate_grade(archetype, is_formative, week_in_course=None, course_length=None):
    """Generate a realistic grade based on archetype."""
    if is_formative:
        return None  # Formative assessments don't have numeric grades

    ranges = {
        "high_performer": (65, 95),
        "average": (48, 72),
        "struggling": (15, 50),
        "improving": (45, 75),
        "declining": (25, 60),
    }

    low, high = ranges[archetype]

    # Improving students get better grades on later assessments
    if archetype == "improving" and week_in_course and course_length:
        progress = week_in_course / course_length
        low = int(low + 15 * progress)
        high = int(high + 10 * progress)

    # Declining students get worse grades on later assessments
    if archetype == "declining" and week_in_course and course_length:
        progress = week_in_course / course_length
        low = int(max(10, low - 20 * progress))
        high = int(max(30, high - 15 * progress))

    grade = random.randint(min(low, high), max(low, high))
    return max(0, min(100, grade))


def generate_assessment_upload(archetype, is_formative):
    """Determine if assessment was uploaded, and its status."""
    # Upload probability by archetype
    upload_probs = {
        "high_performer": 0.95,
        "average": 0.80,
        "struggling": 0.55,
        "improving": 0.70,
        "declining": 0.60,
    }

    # Late probability (given it was uploaded)
    late_probs = {
        "high_performer": 0.05,
        "average": 0.15,
        "struggling": 0.30,
        "improving": 0.20,
        "declining": 0.25,
    }

    p_upload = upload_probs[archetype]
    p_late = late_probs[archetype]

    if random.random() > p_upload:
        return "No", "Not Completed", None

    if random.random() < p_late:
        return "Yes", "Late", "late"
    else:
        return "Yes", "Completed", "on_time"


# ============================================================
# STUDENT GENERATION
# ============================================================

def generate_student_id():
    """Generate a random student ID like STU-24-12345."""
    year = random.choice(["24", "25"])
    num = random.randint(10000, 99999)
    return f"STU-{year}-{num}"


def generate_email(first_name, last_name, used_emails):
    """Generate a unique email address."""
    base = f"{first_name.lower()}.{last_name.lower()}"
    # Clean special characters
    base = base.replace("'", "").replace(" ", "")
    email = f"{base}@student.esl.ac.uk"
    # Ensure uniqueness
    counter = 1
    while email in used_emails:
        email = f"{base}{counter}@student.esl.ac.uk"
        counter += 1
    used_emails.add(email)
    return email


def generate_student(first_name, last_name, email, student_id, course, archetype, ai_engagement, ai_assessment):
    """Generate a complete student row."""
    course_length = course["length"]
    assessment_structure = get_assessment_structure(course_length)

    # Generate attendance
    attendance = generate_attendance(archetype, course_length)

    # Build the row
    row = [first_name, last_name, email, student_id, course["id"], str(course_length)]

    # Weeks 1-16 with engagement notes
    for week in range(1, 17):
        if week <= course_length:
            state = attendance[week - 1]
            note = get_engagement_note(archetype, state, ai_engagement)
            row.extend([state, note])
        else:
            row.extend(["", ""])

    # Assessments
    for assess_key, assess_label in [
        ("formative1", "Formative 1"),
        ("formative2", "Formative 2"),
        ("summative1", "Summative 1"),
        ("summative2", "Summative 2"),
    ]:
        is_formative = "formative" in assess_key
        is_active = assessment_structure[assess_key]

        if not is_active:
            row.extend(["Not Given", "", "", ""])
        else:
            uploaded, status, timing = generate_assessment_upload(archetype, is_formative)

            if uploaded == "No":
                # Not uploaded
                feedback = get_assessment_feedback(None, "Not Completed", is_formative, ai_assessment)
                row.extend(["No", "Not Completed", "", feedback])
            else:
                if is_formative:
                    # Formative: Yes/No grade (meets expectations or not)
                    meets = random.random() < (
                        0.90 if archetype == "high_performer" else
                        0.65 if archetype == "average" else
                        0.30 if archetype == "struggling" else
                        0.55 if archetype == "improving" else
                        0.45
                    )
                    grade_display = "Pass" if meets else "Refer"
                    grade_for_feedback = 60 if meets else 30
                    feedback = get_assessment_feedback(
                        grade_for_feedback, status, True, ai_assessment
                    )
                    row.extend([uploaded, status, grade_display, feedback])
                else:
                    # Summative: numeric grade
                    # Determine rough week when assessment would happen
                    if assess_key == "summative1":
                        assess_week = int(course_length * 0.6)
                    else:
                        assess_week = course_length
                    grade = generate_grade(archetype, False, assess_week, course_length)
                    feedback = get_assessment_feedback(
                        grade, status, False, ai_assessment
                    )
                    row.extend([uploaded, status, f"{grade}%", feedback])

    return row


# ============================================================
# MAIN GENERATION
# ============================================================

def generate_dataset(size, ai_engagement, ai_assessment):
    """Generate a complete dataset of the given size."""
    courses = get_courses_for_size(size)
    used_names = set()
    used_emails = set()
    all_rows = []

    for course in courses:
        for i in range(course["count"]):
            # Pick unique name
            while True:
                first = random.choice(FIRST_NAMES)
                last = random.choice(LAST_NAMES)
                name_key = f"{first}_{last}"
                if name_key not in used_names:
                    used_names.add(name_key)
                    break

            email = generate_email(first, last, used_emails)
            student_id = generate_student_id()
            archetype = pick_archetype()

            row = generate_student(
                first, last, email, student_id, course, archetype,
                ai_engagement, ai_assessment
            )
            all_rows.append(row)

    # Shuffle so students from different courses are mixed
    random.shuffle(all_rows)
    return all_rows


def write_csv_file(filename, rows):
    """Write rows to CSV file."""
    filepath = os.path.join(OUTPUT_DIR, filename)
    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(HEADER)
        writer.writerows(rows)
    print(f"  Written: {filepath} ({len(rows)} students)")


def main():
    print("=" * 60)
    print("Marcel Student Data Generator")
    print("=" * 60)

    # Create output directory
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    # Load AI-generated feedback
    print("\nLoading AI-generated feedback...")
    ai_engagement, ai_assessment = load_ai_feedback()

    if ai_engagement:
        print("  Using AI engagement notes")
    else:
        print("  Using fallback engagement templates")

    if ai_assessment:
        print("  Using AI assessment feedback")
    else:
        print("  Using fallback assessment templates")

    # Generate datasets
    for size in SIZES:
        print(f"\nGenerating {size}-student dataset...")
        rows = generate_dataset(size, ai_engagement, ai_assessment)
        write_csv_file(f"StudentData_{size}.csv", rows)

    print("\n" + "=" * 60)
    print("Generation complete!")
    print(f"Files saved to: {OUTPUT_DIR}/")
    print("=" * 60)


if __name__ == "__main__":
    random.seed(42)  # For reproducibility during testing
    main()
