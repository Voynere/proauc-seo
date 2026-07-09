"""Japanese → Russian title and grade translation for motorhome imports."""

from __future__ import annotations

import re
import unicodedata

from .schema import NormalizedListing

# Longest-first body-type and category terms (JP → RU).
BODY_TYPE_MAP: dict[str, str] = {
    "キャンピングカー": "Автодом",
    "キャブコン": "кабина-кон",
    "バンコン": "ван-кон",
    "バスコン": "автобус-кон",
    "軽キャン": "лёгкий автодом",
    "トラックキャンパー": "грузовой автодом",
    "キャンパー": "кемпер",
    "モーターホーム": "моторхом",
    "キャンピング": "кемпинг",
}

# Known brand / model fragments (fullwidth katakana or normalized Latin).
KNOWN_NAMES: dict[str, str] = {
    "カムロード": "Kamroad",
    "ナッツRV": "Nuts RV",
    "ナッツ": "Nuts",
    "クレソンボヤージュ": "Cresson Boyage",
    "クレソンボヤージュX": "Cresson Boyage X",
    "ボヤージュ": "Boyage",
    "トヨタ": "Toyota",
    "ニッサン": "Nissan",
    "ホンダ": "Honda",
    "マツダ": "Mazda",
    "スズキ": "Suzuki",
    "ダイハツ": "Daihatsu",
    "いすゞ": "Isuzu",
    "三菱": "Mitsubishi",
    "ベンツ": "Mercedes-Benz",
    "メルセデス": "Mercedes",
    "フォルクスワーゲン": "Volkswagen",
    "フィアット": "Fiat",
    "キャタピラー": "Caterpillar",
    "コマツ": "Komatsu",
}

# Auction / repair-history grade values (JP → RU).
GRADE_MAP: dict[str, str] = {
    "無": "—",
    "無し": "—",
    "なし": "—",
    "有": "Есть",
    "有り": "Есть",
    "あり": "Есть",
    "未評価": "Без оценки",
    "評価なし": "Без оценки",
    "不明": "Не указано",
}

# Katakana → Latin ( Hepburn-style, sufficient for brand/model display ).
_KATA_TO_LATIN: dict[str, str] = {
    "ア": "a",
    "イ": "i",
    "ウ": "u",
    "エ": "e",
    "オ": "o",
    "カ": "ka",
    "キ": "ki",
    "ク": "ku",
    "ケ": "ke",
    "コ": "ko",
    "サ": "sa",
    "シ": "shi",
    "ス": "su",
    "セ": "se",
    "ソ": "so",
    "タ": "ta",
    "チ": "chi",
    "ツ": "tsu",
    "テ": "te",
    "ト": "to",
    "ナ": "na",
    "ニ": "ni",
    "ヌ": "nu",
    "ネ": "ne",
    "ノ": "no",
    "ハ": "ha",
    "ヒ": "hi",
    "フ": "fu",
    "ヘ": "he",
    "ホ": "ho",
    "マ": "ma",
    "ミ": "mi",
    "ム": "mu",
    "メ": "me",
    "モ": "mo",
    "ヤ": "ya",
    "ユ": "yu",
    "ヨ": "yo",
    "ラ": "ra",
    "リ": "ri",
    "ル": "ru",
    "レ": "re",
    "ロ": "ro",
    "ワ": "wa",
    "ヲ": "wo",
    "ン": "n",
    "ガ": "ga",
    "ギ": "gi",
    "グ": "gu",
    "ゲ": "ge",
    "ゴ": "go",
    "ザ": "za",
    "ジ": "ji",
    "ズ": "zu",
    "ゼ": "ze",
    "ゾ": "zo",
    "ダ": "da",
    "ヂ": "ji",
    "ヅ": "zu",
    "デ": "de",
    "ド": "do",
    "バ": "ba",
    "ビ": "bi",
    "ブ": "bu",
    "ベ": "be",
    "ボ": "bo",
    "パ": "pa",
    "ピ": "pi",
    "プ": "pu",
    "ペ": "pe",
    "ポ": "po",
    "ヴ": "vu",
    "ー": "",
    "ッ": "",
    "ャ": "ya",
    "ュ": "yu",
    "ョ": "yo",
    "ァ": "a",
    "ィ": "i",
    "ゥ": "u",
    "ェ": "e",
    "ォ": "o",
}

_HW_KATA = "ｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝ"
_FW_KATA = "ヲァィゥェォャュョッーアイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワン"
_HW_DAKUTEN = {"ﾞ": "゛", "ﾟ": "゜"}

_JP_RE = re.compile(r"[\u3040-\u30ff\u31f0-\u31ff\uff66-\uff9f\u4e00-\u9fff]")
_GRADE_POINTS_RE = re.compile(r"^(\d+(?:\.\d+)?)\s*点?$")
_GRADE_LETTER_RE = re.compile(r"^([RS](?:A)?(?:A)?)$", re.I)


def contains_japanese(text: str) -> bool:
    return bool(text and _JP_RE.search(text))


def needs_translation(text: str | None) -> bool:
    return bool(text and contains_japanese(text))


def normalize_japanese_text(text: str) -> str:
    """NFKC + halfwidth katakana → fullwidth."""
    if not text:
        return ""
    text = unicodedata.normalize("NFKC", text)
    out: list[str] = []
    for ch in text:
        if ch in _HW_DAKUTEN:
            out.append(_HW_DAKUTEN[ch])
            continue
        idx = _HW_KATA.find(ch)
        if idx >= 0:
            out.append(_FW_KATA[idx])
        else:
            out.append(ch)
    return "".join(out)


def transliterate_katakana(text: str) -> str:
    """Convert a katakana run to title-cased Latin."""
    if not text:
        return ""
    chars: list[str] = []
    for ch in text:
        latin = _KATA_TO_LATIN.get(ch)
        if latin is None:
            continue
        chars.append(latin)
    raw = "".join(chars)
    if not raw:
        return text
    # Simple syllable join: capitalize word starts after vowel boundaries.
    return _title_case_latin(raw)


def _title_case_latin(raw: str) -> str:
    """Heuristic title case for transliterated katakana."""
    if not raw:
        return raw
    # Split on common boundaries already present in source (spaces, digits).
    parts = re.split(r"(\d+|[A-Za-z]+)", raw)
    result: list[str] = []
    for part in parts:
        if not part:
            continue
        if part.isdigit():
            result.append(part)
        elif part.isascii() and part.isalpha():
            result.append(part.capitalize())
        else:
            result.append(part[:1].upper() + part[1:] if part else part)
    return " ".join(result)


def _translate_katakana_segment(segment: str) -> str:
    """Greedy known-name match, then transliterate remainder."""
    normalized = normalize_japanese_text(segment)
    if not normalized:
        return segment

    parts: list[str] = []
    rest = normalized
    while rest:
        match_key: str | None = None
        match_val: str | None = None
        for key, value in sorted(KNOWN_NAMES.items(), key=lambda kv: len(kv[0]), reverse=True):
            if rest.startswith(key):
                match_key = key
                match_val = value
                break
        if match_key and match_val:
            parts.append(match_val)
            rest = rest[len(match_key) :]
            continue

        # Take one katakana char and transliterate run until next known prefix.
        m = re.match(r"[\u30a0-\u30ff]+", rest)
        if m:
            chunk = m.group()
            parts.append(transliterate_katakana(chunk))
            rest = rest[len(chunk) :]
            continue

        # Latin/digit chunk glued to katakana (e.g. RV, X, 4WD).
        m = re.match(r"[A-Za-z0-9]+", rest)
        if m:
            parts.append(m.group())
            rest = rest[len(m.group()) :]
            continue

        parts.append(rest[0])
        rest = rest[1:]

    return " ".join(p for p in parts if p)


def _translate_remaining_japanese(text: str) -> str:
    """Translate leftover katakana/hiragana segments in mixed text."""
    if not text or not contains_japanese(text):
        return text

    parts: list[str] = []
    pos = 0
    pattern = re.compile(r"[\u3040-\u30ff\u31f0-\u31ff\uff66-\uff9f]+")
    for match in pattern.finditer(text):
        if match.start() > pos:
            parts.append(text[pos : match.start()])
        parts.append(_translate_katakana_segment(match.group()))
        pos = match.end()
    if pos < len(text):
        parts.append(text[pos:])
    return " ".join(p for p in " ".join(parts).split() if p)


def translate_title(title: str) -> str:
    """Translate a Fujicars-style motorhome title JP → RU."""
    if not title or not contains_japanese(title):
        return title.strip()

    text = normalize_japanese_text(title)
    for jp, ru in sorted(BODY_TYPE_MAP.items(), key=lambda kv: len(kv[0]), reverse=True):
        text = text.replace(jp, f" {ru} ")

    text = _translate_remaining_japanese(text)
    text = re.sub(r"\s+", " ", text).strip()
    return text or title.strip()


def translate_grade(grade: str | None) -> str | None:
    """Translate auction / repair-history grade to Russian display text."""
    if grade is None:
        return None
    raw = grade.strip()
    if not raw:
        return None

    normalized = normalize_japanese_text(raw)
    if normalized in GRADE_MAP:
        return GRADE_MAP[normalized]

    points = _GRADE_POINTS_RE.match(normalized)
    if points:
        return points.group(1)

    letter = _GRADE_LETTER_RE.match(normalized)
    if letter:
        return letter.group(1).upper()

    for jp, ru in GRADE_MAP.items():
        if jp in normalized:
            return ru

    points_inline = re.search(r"(\d+(?:\.\d+)?)\s*点", normalized)
    if points_inline:
        return points_inline.group(1)

    if not contains_japanese(raw):
        return raw

    return raw


def apply_translation(listing: NormalizedListing) -> NormalizedListing:
    """Apply JP→RU translation to listing title and properties.grade."""
    if listing.source in ("fujicars", "bobaedream"):
        listing.title = translate_title(listing.title)
        if listing.properties.grade:
            listing.properties.grade = translate_grade(listing.properties.grade)
        for param in listing.parameters:
            if param.name in ("Оценка", "修復歴") and param.value:
                translated = translate_grade(param.value)
                if translated:
                    param.value = translated
    return listing
