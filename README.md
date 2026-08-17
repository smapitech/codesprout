# 🌱 Child’s Bridge CodeSprout

## Game-Based Digital Skills & Early Coding Platform

**CodeSprout** is the learning engine behind the early-years programme of **Child’s Bridge AI Academy**.

It is designed to help young learners build essential computer skills, coding confidence, and digital problem-solving ability through structured learning worlds, missions, games, projects, assignments, and guided progression.

CodeSprout forms part of the wider **FutureBridge AI** education ecosystem developed by **SMAPIS Technologies — Smart Multi-Agent Platform & Intelligent Systems**.

🌐 **Live Platform:** https://childsbridge.site

---

# 🎯 The Problem

Many young learners are introduced to technology through disconnected coding exercises, short games, or one-off tutorials.

This often creates three challenges:

* Progress is difficult to measure
* Learners may complete activities without mastering the underlying skill
* Parents and teachers have limited visibility into what the child can actually do

CodeSprout addresses this by combining structured curriculum, mastery-based progression, game-based learning, guided assignments, coding projects, and role-specific dashboards within one learning platform.

---

# 💡 Our Solution

CodeSprout helps learners move through a progressive digital-learning journey:

**Computer Readiness → Keyboard & Mouse Skills → Coding Foundations → Web Development → Projects → Portfolio**

The platform is designed around practical progression rather than simply completing lessons.

Learners advance through structured stages while teachers and parents can monitor progress, assignments, projects, and learning outcomes.

---

# 🧩 Learning Architecture

The current curriculum structure includes:

* **12 Learning Worlds**
* **48 Weekly Units**
* **144 Lessons**
* **576 Learning Stages**
* **48 Tracked Skills**

The curriculum hierarchy follows:

**Curriculum → World → Weekly Unit → Lesson → Stage**

This structure allows learning content to grow progressively while maintaining clear prerequisites, progression rules, and measurable outcomes.

---

# 🎮 Game-Based Learning

CodeSprout includes a game engine designed to make foundational computer skills engaging and practical.

Learning experiences can include:

* Keyboard practice
* Typing challenges
* Mouse-control activities
* Drag-and-drop tasks
* Computer-navigation activities
* Coding games
* Interactive exercises
* Timed challenges
* Skill-based missions

Game sessions can track learner performance and support future progress analytics.

---

# 🧑‍🎓 Student Experience

Learners have access to a child-friendly dashboard designed around missions, progress, and practical activities.

Core learner features include:

* Personal learning journey
* Assigned missions
* Games
* Coding exercises
* Project work
* Progress tracking
* Resume-and-continue learning
* Structured curriculum access
* Safe authentication
* Age-appropriate interface

The child interface is designed to remain simple, visual, touch-friendly, and distraction-free.

---

# 🧑‍🏫 Teacher Experience

Teachers can:

* Browse published curriculum
* Assign learning activities
* Review learner progress
* Mark assignments
* Provide feedback
* Monitor game performance
* Review coding projects
* Track class activity
* Support learners through structured progression

Teachers only see curriculum and resources appropriate to their role and assigned learners.

---

# 👨‍👩‍👧 Parent Experience

Parents can view progress for linked children.

Parent features include:

* Learning progress
* Assignment results
* Released teacher feedback
* Project summaries
* Learning activity visibility
* Child-specific dashboards

This gives parents clearer insight into what their child is actually learning and building.

---

# 🛠️ Administration

Administrators manage the platform through a dedicated workspace.

Administrative capabilities include:

* User management
* Teacher accounts
* Parent accounts
* Child learner accounts
* Academic cohorts
* Classes
* Teacher assignments
* Parent-child relationships
* Curriculum creation
* Curriculum publishing
* Assignment management
* Game management
* Application settings
* Audit records

---

# 📚 Curriculum Management

CodeSprout includes a structured curriculum engine supporting:

* Draft content
* Published content
* Archived content
* Learning prerequisites
* Curriculum ordering
* World progression
* Lesson progression
* Stage progression
* Curriculum preview
* Import and export
* Structured JSON curriculum data

Only published curriculum is visible to learners.

---

# 📝 Assignment Engine

The platform supports practical learning assignments with:

* Versioned assignments
* Automatic and manual grading
* Individual learner allocation
* Class allocation
* Learner groups
* Autosave
* Resume functionality
* Submission workflows
* Teacher marking
* Feedback
* Return-for-retry workflows
* Parent result visibility

Assignments are designed to support both automated exercises and teacher-reviewed practical work.

---

# 🌐 Early Web Development Engine

CodeSprout includes structured early web-development activities.

Learners can progressively work with:

* HTML
* CSS
* Basic webpage structure
* Guided web projects
* Practical coding exercises
* Live preview
* Teacher review

Learner-generated HTML is sanitised before preview and displayed within a restricted sandbox environment.

---

# 🔐 Child Safety & Access Control

Child safety is a core part of the platform architecture.

The platform includes:

* Role-based permissions
* Separate child authentication
* Hashed learner PINs
* Login throttling
* Restricted curriculum visibility
* Parent-child relationship controls
* Teacher-class permissions
* Private learner projects
* Controlled project sharing
* Audit records

Public registration for child learners is intentionally disabled.

---

# 🏗️ Technology Stack

CodeSprout is built with:

* **Laravel 12**
* **PHP**
* **React**
* **Inertia.js**
* **TypeScript**
* **Vite**
* **MySQL / MariaDB**
* **Spatie Roles & Permissions**
* **REST-style APIs**
* **Automated Testing**
* **Responsive UI Architecture**

---

# 🧱 Platform Architecture

The application is structured around role-based areas:

* Administrator
* Teacher
* Parent
* Child

Core platform domains include:

* Identity
* Profiles
* Curriculum
* Classes
* Assignments
* Games
* Projects
* Progress
* Relationships
* Settings
* Audit logs

The architecture is designed to support continued expansion into AI-assisted learning, deeper progress analytics, adaptive pathways, and advanced coding environments.

---

# 🤖 AI & Future Development

CodeSprout is part of the wider **FutureBridge AI** vision.

Future development areas include:

* AI-assisted tutoring
* Personalised learning pathways
* Skills-gap detection
* Intelligent recommendations
* Adaptive difficulty
* AI-assisted project feedback
* Parent progress summaries
* Teacher support tools
* Learning analytics
* Portfolio development
* Career-path preparation for older learners

---

# 🚀 Project Status

**Status:** Active Development

CodeSprout was originally developed through private and local development workflows and has been progressively consolidated into the Child’s Bridge platform and GitHub engineering portfolio.

The platform continues to evolve as part of the broader Child’s Bridge and FutureBridge AI roadmap.

---

# 📸 Screenshots

Platform screenshots should be stored under:

```text
docs/screenshots/
```

Recommended screenshots include:

* `landing-page.png`
* `child-dashboard.png`
* `teacher-dashboard.png`
* `parent-dashboard.png`
* `curriculum-builder.png`
* `assignment-view.png`
* `game-engine.png`
* `html-project.png`

Example:

```markdown
![Child Dashboard](docs/screenshots/child-dashboard.png)
```

---

# 🧪 Local Development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Run automated tests with:

```bash
php artisan test
```

---

# 🔒 Security Note

Sensitive production information is not stored in this repository.

The following should never be committed:

* `.env`
* API keys
* database passwords
* SMTP credentials
* payment credentials
* production backups
* private learner data
* production uploads

---

# 🌍 About Child’s Bridge AI Academy

**Child’s Bridge AI Academy** prepares children and teenagers for the future through practical technology education.

Learning areas include:

* Coding
* Python
* JavaScript
* Web development
* Artificial intelligence
* AI agents
* Git and GitHub
* Software projects
* Problem-solving
* Digital responsibility
* Portfolio development

🌐 https://childsbridge.site

---

# 🧠 About SMAPIS Technologies

**SMAPIS Technologies** means:

## Smart Multi-Agent Platform & Intelligent Systems

SMAPIS Technologies develops:

* Artificial intelligence platforms
* Multi-agent systems
* SaaS products
* Education technology
* Business automation
* Intelligent software systems

🌐 https://smapis.net

---

# 🤝 Collaboration

We are open to collaboration in areas including:

* Education technology
* AI-assisted learning
* Game-based education
* Coding education
* Learning analytics
* School technology
* AI agents
* SaaS development
* International education partnerships

---

# 📫 Links

**Child’s Bridge AI Academy**
https://childsbridge.site

**SMAPIS Technologies**
https://smapis.net

**GitHub**
https://github.com/smapitech

---

> **Building digital confidence early, one skill, one project, and one learner at a time.**
