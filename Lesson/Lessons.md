## Lesson 2 — Ano ang ginagawa ng bawat matched item

1. **Embedding PHP Code**
   - `include '_header.php'`
     - Pinapalo-load ng page ang shared header at nagsisimula ng PHP session.
     - Ito ang dahilan kung bakit magagamit ang shared navbar at session sa buong page.
   - `include '_footer.php'`
     - Pinapalo-load ang shared footer sa dulo ng bawat page.

2. **PHP Variables**
   - `$pageTitle`, `$activePage`, `$extraHead`
     - Nagbibigay ng dynamic page title, active nav state, at karagdagang CSS/JS sa header.
   - `$courtImages`, `$bookingOptions`, `$coaches`
     - Nag-store ng structured data para ma-loop at ma-render ang court page dynamically.
   - `$users`, `$loginError`, `$email`, `$password`
     - Ginagamit sa login handling para i-validate user input at magpakita ng error message.
   - `$events`, `$steps`, `$rules`
     - Content arrays sa index.php para ma-display ang homepage sections.
   - `$galleryImages`, `$faqs`
     - Content arrays sa social-play.php para sa gallery at FAQ.

3. **Data Types**
   - Arrays, strings, numbers
     - Ginagamit para gumawa ng listahan ng images, coach info, form options, at homepage content.
   - `date('Y')`
     - Kinuha ang current year; karaniwang ginagamit sa footer para laging updated ang copyright.
   - `filter_input(...)` at `trim(...)`
     - Nililinis at tine-test ang user input bago i-process ang login form.

4. **PHP Operators**
   - String concatenation `.` at `strtoupper(...)`
     - Ginagamit para lumikha ng order number o uppercase text.
   - `&&`, `===`
     - Ginagamit sa login validation para sabay na i-check ang email at password.
   - Ternary operator `?:`
     - Ginagamit sa HTML output para mag-assign ng `is-active` class depende sa kondisyon.

5. **Logic Control**
   - `if`
     - Pinipili kung mag-redirect ang user, kung may login error, o kung dapat i-display ang isang section.
   - `foreach`
     - Ginagamit para ulitin ang arrays at gumawa ng HTML para sa bawat item.
   - `for`
     - Ginagamit para mag-loop ng fixed na bilang ng elements, tulad ng calendar days.
   - `switch`
     - Hindi present sa project ngayon, kaya walang place para rito sa kasalukuyang code.

6. **Looping**
   - `foreach`
     - Ginagamit sa courts.php, index.php, social-play.php para gawing dynamic ang content.
   - `for`
     - Ginagamit sa courts.php at social-play.php para gumawa ng fixed sequence tulad ng calendar days.
   - `while` / `do..while`
     - Hindi nabanggit sa repo map, ibig sabihin wala sa kasalukuyang PHP files.

7. **Building Functions**
   - `function pickled_step_icon(string $icon): string`
     - Gumagawa ng reusable HTML icon output at pinapadali ang pag-render ng list items sa index.php.

8. **Event Driven PHP / Form processing**
   - `$_SERVER['REQUEST_METHOD'] === 'POST'`
     - Tinitiyak na ang login code ay tatakbo lang kapag na-submit ang form.
   - `$_POST`
     - Kinuha ang form values para sa email at password.
   - `$_SESSION`
     - Ini-store ang authenticated user info at ginagamit para magpakita ng logout o welcome state.
   - `header('Location: index.php')`
     - Nagre-redirect pagkatapos mag-login o kapag naka-login na.

---

## Lesson 3 — Ano ang ginagawa ng bawat matched item

### Present
- `date()`
  - Kumuha ng petsa/time value; ginamit sa courts.php at _footer.php para sa dynamic year/value.
- `trim()`
  - Nililinis ang password input mula sa extra spaces bago i-validate.
- `strtoupper()`
  - Ginawang uppercase ang bahagi ng string output.
- `filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)`
  - Validates na tamang email format ang ipinadala ng user.

### Missing sa repo
- `phpinfo()`
  - Ginagamit para makita ang server/PHP setup; wala sa project.
- `preg_match()`
  - Ginagamit para regex validation; wala sa project.
- `strcmp()`, `strcasecmp()`
  - Ginagamit para exact string comparison; wala sa project.
- `strpos()`
  - Ginagamit para hanapin ang substring sa string; wala sa project.
- `str_word_count()`, `strlen()`
  - Ginagamit para bilangin salita/character length; wala sa project.
- `strtotime()`
  - Ginagamit para parse ng date string; wala sa project.
- Server-side image handling
  - Hindi present ang image upload/conversion scripts sa repo.

---

## Lesson 4 — Ano ang ginagawa ng bawat matched item

### Missing sa repo
- `class ...`
  - Wala pa sa current code; kailangan kung gusto ang OOP implementation.
- `__get()`, `__set()`
  - Wala pa; ito ay magic methods para controlled access sa private properties.
- `extends`
  - Wala pa; ginagamit para inheritance at parent-child class relationship.
- `private`, `public`
  - May basic use sa Lesson 4 concept ngunit wala sa current repo sa code.

> Sa madaling salita: ang repo ay may magandang Lesson 2 coverage at partial Lesson 3, ngunit wala pang aktwal na Lesson 4 object-oriented code.

If you want, I can also turn this into a one-page “project coverage report” with exact matched files and recommended code additions per lesson.