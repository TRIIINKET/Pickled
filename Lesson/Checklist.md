
### Lesson 2

- **Embedding PHP Code**
  - Makikita sa index.php, courts.php, social-play.php, login.php, contact.php, private.php
  - `include '_header.php';` at `include '_footer.php';` ay PHP embedding kasama ang HTML

- **PHP Variables**
  - courts.php: `$pageTitle`, `$activePage`, `$courtImages`, `$bookingOptions`, `$coaches`, `$bookingReference`
  - login.php: `$loginError`, `$users`, `$email`, `$password`
  - index.php: `$events`, `$steps`, `$rules`
  - social-play.php: `$galleryImages`, `$faqs`

- **Data types**
  - String, integer, boolean, array sa lahat ng page data
  - courts.php uses arrays of strings and nested arrays
  - login.php uses strings and booleans in validation checks

- **PHP Operators**
  - String concatenation `.` sa courts.php at _footer.php
  - Comparison operators `===`, `!==`
  - Logical operators `&&`
  - Ternary operator in courts.php: `$index === 0 ? 'is-active' : ''`
  - Examples: login.php, courts.php, _header.php, _footer.php

- **Logic Control (`if`, `elseif`, `switch`)**
  - `if` used in:
    - login.php form validation
    - courts.php date calendar rendering
    - _header.php session start
    - _navbar.php logged-in display
  - `elseif` not obvious in current files
  - `switch` — wala sa files ngayon

- **Looping (`while`, `do..while`, `for`, `foreach`)**
  - `foreach` used heavily:
    - courts.php for courts, thumbs, coaches, coach options
    - index.php for steps/events/rules
    - social-play.php for gallery and FAQs
  - `for` used in:
    - courts.php calendar day generation
    - social-play.php calendar day generation
  - `while` / `do..while` — wala sa current code

- **Building Functions**
  - index.php defines `pickled_step_icon(string $icon): string`
  - This is the only PHP user-defined function in current files

- **Event Driven PHP (links and processing form data)**
  - login.php processes form POST data with:
    - `$_SERVER['REQUEST_METHOD']`
    - `filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)`
    - `$_POST['password']`
    - `$_SESSION['user']`
  - logout.php handles logout and redirect

---

### Lesson 3 — Partial, pero may ilang bahagi
Hindi lahat ng Lesson 3 examples ay narito, pero may ilang matching features.

- **PHP extensions**
  - Wala sa code (walang `phpinfo()` or extension listing page)

- **Text Functions / string processing**
  - May `trim()` sa login.php
  - May `strtoupper()` sa courts.php
  - `htmlspecialchars()` sa maraming template output
- **Testing String Values**
  - Wala ang specific examples tulad ng `strpos()`, `strcasecmp()`, `strcmp()`, `str_word_count()` sa current files
- **Date and Time Functions**
  - May `date('ymd')` sa courts.php
  - May `date('Y')` sa _footer.php
- **Image Handling Functions**
  - Wala ang server-side image upload code (`imageupload.php`, `imageconvert.php` style)
  - Current code ay HTML/CSS image display lang

---

### Lesson 4 — Wala sa Code
Sa kasalukuyang repository, wala pang OOP / class-based PHP implementation.

- Walang `class ...` na PHP definition
- Walang `__get()`, `__set()`, inheritance, extends, at iba pang OOP structure
- Kaya lesson 4 ay hindi present sa current code

---

## Summary
- `Lesson 2` → halos present at aktibo sa existing pages
- `Lesson 3` → partial lamang; may string/date function usage pero walang extension/phpinfo at advanced string testing
- `Lesson 4` → wala sa current files

Kung gusto mo, pwede kong i-list naman ang eksaktong filename + line example para sa bawat lesson item na matagumpay na nahanap ko.
