"""Japanese / Korean → Russian title and grade translation for motorhome imports."""

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
    "ケイワークス": "Keiworks",
    "ケイワクス": "Keiworks",
    "デリカ": "Delica",
    "クルーズ": "Cruise",
    "ハイエース": "Hiace",
    "グランドキャビン": "Grand Cabin",
    "キャラバン": "Caravan",
    "セレナ": "Serena",
    "エルグランド": "Elgrand",
    "アルファード": "Alphard",
    "ヴォクシー": "Voxy",
    "ボクシー": "Voxy",
    "スペースギア": "Space Gear",
    "トヨタ": "Toyota",
    "ニッサン": "Nissan",
    "ホンダ": "Honda",
    "マツダ": "Mazda",
    "スズキ": "Suzuki",
    "ダイハツ": "Daihatsu",
    "いすゞ": "Isuzu",
    "三菱": "Mitsubishi",
    "スバル": "Subaru",
    "レクサス": "Lexus",
    "日野": "Hino",
    "ヤマハ": "Yamaha",
    "ベンツ": "Mercedes-Benz",
    "メルセデス": "Mercedes",
    "フォルクスワーゲン": "Volkswagen",
    "フィアット": "Fiat",
    "キャタピラー": "Caterpillar",
    "コマツ": "Komatsu",
    "インターネック": "Internec",
    "東和モータース": "Towa Motors",
    "東和": "Towa",
    "冷蔵庫": "холодильник",
    "新車": "новый",
    "中古": "б/у",
    "未使用": "не использовался",
    "ワンオーナー": "один владелец",
    "禁煙": "некурящий",
    "車検": "техосмотр",
    "サンルーフ": "люк",
    "バックカメラ": "камера заднего вида",
    "ナビ": "навигация",
    "ETC": "ETC",
    "エアコン": "кондиционер",
    "ディーゼル": "дизель",
    "ガソリン": "бензин",
    # Builders / conversion kits (camping cars).
    "トヨファクトリー": "Toyo Factory",
    "グリーンバディ": "Green Body",
    "ランドタイプ": "Land Type",
    "エブリー": "Every",
    "エブリイ": "Every",
    "カーショップアシスト": "Car Shop Assist",
    "キャンタライ": "Cantrai",
    "ぷちキャンタライ": "Mini Cantrai",
    "ぷち": "Mini",
    "ハイゼット": "Hijet",
    "ラクンタイプ": "Raccoon Type",
    "バンテック": "Vantec",
    "バンテックル": "Vantec L",
    "AZ-MAX": "AZ-MAX",
    # Equipment phrases (often glued to Latin in titles).
    "FF暖房": "FF отопление",
    "家用エアコン": "бытовой кондиционер",
    "2段ベッド": "двухъярусная кровать",
    "2段ベット": "двухъярусная кровать",
    "ベッド": "кровать",
    "ベット": "кровать",
    "インバーター": "инвертор",
    "インバータ": "инвертор",
    "架装": "оборудование",
    "新規": "новый",
    "家庭用": "бытовой",
    "顔替え": "рестайлинг",
    "顔替": "рестайлинг",
}

# Kanji fragments common in motorhome titles/specs (JP → RU).
KANJI_MAP: dict[str, str] = {
    "暖房": "отопление",
    "架装": "оборудование",
    "新規": "новый",
    "家用": "бытовой",
    "家庭用": "бытовой",
    "軽": "лёгкий",
    "2段": "2-ярусный",
    "段": "ярус",
    "顔替え": "рестайлинг",
    "顔替": "рестайлинг",
    "家": "бытовой",
    "冷": "холод",
    "温": "тепло",
    "風": "вентиляция",
    "電": "электро",
    "装": "оборудование",
    "備": "опции",
}

# Fix katakana transliteration that does not match established Latin spellings.
_LATIN_FIXES: list[tuple[re.Pattern[str], str]] = [
    (re.compile(r"\bDelika\b", re.I), "Delica"),
    (re.compile(r"\bDerika\b", re.I), "Delica"),
    (re.compile(r"\bKeiwakusu\b", re.I), "Keiworks"),
    (re.compile(r"\bKuruzu\b", re.I), "Cruise"),
    (re.compile(r"\bToifuakutori\b", re.I), "Toyo Factory"),
    (re.compile(r"\bGurinbadei\b", re.I), "Green Body"),
    (re.compile(r"\bRandoteipi\b", re.I), "Land Type"),
    (re.compile(r"\bEburii\b", re.I), "Every"),
    (re.compile(r"\bKashiyopuashisuto\b", re.I), "Car Shop Assist"),
    (re.compile(r"\bKiyantorai\b", re.I), "Cantrai"),
    (re.compile(r"\bHaizeto\b", re.I), "Hijet"),
    (re.compile(r"\bRakun\s+Taipu\b", re.I), "Raccoon Type"),
    (re.compile(r"\bBantekujiru\b", re.I), "Vantec L"),
    (re.compile(r"\bEakon\b", re.I), "кондиционер"),
    (re.compile(r"\bBedo\b", re.I), "кровать"),
    (re.compile(r"\bFF\s*暖房\b"), "FF отопление"),
    (re.compile(r"\b2段\s+Bedo\b"), "двухъярусная кровать"),
    (re.compile(r"\b2段\s+кровать\b"), "двухъярусная кровать"),
    (re.compile(r"\b家\s+Eakon\b"), "бытовой кондиционер"),
    (re.compile(r"\b520\s+家\s+кондиционер\b"), "520 бытовой кондиционер"),
    (re.compile(r"\b顔替\s+え\b"), "рестайлинг"),
    (re.compile(r"\bぷ\s+ち\b"), "Mini"),
    (re.compile(r"\s+え\b"), ""),
]

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

# Korean body / category terms (longest first).
KO_BODY_TYPE_MAP: dict[str, str] = {
    "캠핑카/이동사무차": "Автодом",
    "4WD 캠핑카": "Автодом 4WD",
    "캠핑카": "Автодом",
    "캠핑": "кемпинг",
    "특장업체": "",
    "이동사무차": "мобильный офис",
    "모터홈": "моторхом",
    "캠퍼밴": "кемпервэн",
    "캠퍼": "кемпер",
}

# Korean brand / model / badge fragments (longest first).
KO_KNOWN_NAMES: dict[str, str] = {
    "더 뉴 그랜드 스타렉스": "Grand Starex",
    "더뉴 그랜드 스타렉스": "Grand Starex",
    "더뉴그랜드스타렉스": "Grand Starex",
    "그랜드 스타렉스": "Grand Starex",
    "그랜드스타렉스": "Grand Starex",
    "스타렉스": "Starex",
    "스타리아": "Staria",
    "포터 II": "Porter II",
    "포터2": "Porter II",
    "포터": "Porter",
    "봉고 III": "Bongo",
    "봉고3": "Bongo",
    "봉고": "Bongo",
    "카니발": "Carnival",
    "e-카운티": "e-County",
    "e카운티": "e-County",
    "카운티": "County",
    "쏠라티": "Solati",
    "마스터": "Master",
    "스프린터": "Sprinter",
    "현대": "Hyundai",
    "기아": "Kia",
    "쌍용": "SsangYong",
    "KG모빌리티": "KG Mobility",
    "르노코리아": "Renault Korea",
    "르노삼성": "Renault Samsung",
    "쉐보레": "Chevrolet",
    "벤츠": "Mercedes-Benz",
    "메르세데스": "Mercedes",
    "폭스바겐": "Volkswagen",
    "포드": "Ford",
    "이베코": "Iveco",
    "디젤": "дизель",
    "가솔린": "бензин",
    "하이브리드": "гибрид",
    "자동": "АКПП",
    "수동": "МКПП",
}

# Canonical chassis models used to reshape Encar titles for /avtodoma/ filter.
_KO_CANONICAL_MODELS: tuple[str, ...] = (
    "Grand Starex",
    "Starex",
    "Staria",
    "Porter II",
    "Porter",
    "Bongo",
    "Carnival",
    "e-County",
    "County",
    "Solati",
    "Master",
    "Sprinter",
)

# Badge / body-type strings that must NOT appear in ACF «Оценка» (properties.grade).
# Encar stores camping Badge here; that is not an auction grade.
_NON_GRADE_VALUES: frozenset[str] = frozenset(
    {
        "캠핑카",
        "4WD 캠핑카",
        "캠핑카/이동사무차",
        "특장업체",
        "이동사무차",
        "Автодом",
        "Автодом 4WD",
        "Спецкузов",
        "Мобильный офис",
        "кемпинг",
        "моторхом",
        "кемпер",
        "кемпервэн",
    }
)

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
# Hangul syllables + jamo (Encar titles/grades).
_KO_RE = re.compile(r"[\u1100-\u11ff\u3130-\u318f\uac00-\ud7af]")
_GRADE_POINTS_RE = re.compile(r"^(\d+(?:\.\d+)?)\s*点?$")
_GRADE_LETTER_RE = re.compile(r"^([RS](?:A)?(?:A)?)$", re.I)
_BRAND_PREFIX_RE = re.compile(
    r"^(Hyundai|Kia|SsangYong|KG Mobility|Renault Korea|Renault Samsung|"
    r"Chevrolet|Mercedes(?:-Benz)?|Volkswagen|Ford|Iveco|Toyota|Nissan|"
    r"Honda|Mazda|Suzuki|Mitsubishi)\s+",
    re.I,
)


def contains_japanese(text: str) -> bool:
    return bool(text and _JP_RE.search(text))


def contains_korean(text: str) -> bool:
    return bool(text and _KO_RE.search(text))


def needs_translation(text: str | None) -> bool:
    return bool(text and (contains_japanese(text) or contains_korean(text)))


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


def _apply_kanji_map(text: str) -> str:
    """Replace leftover kanji fragments (longest match first)."""
    if not text:
        return text
    for jp, ru in sorted(KANJI_MAP.items(), key=lambda kv: len(kv[0]), reverse=True):
        text = text.replace(jp, ru)
    return text


def _fix_latin_spellings(text: str) -> str:
    for pattern, replacement in _LATIN_FIXES:
        text = pattern.sub(replacement, text)
    return text


def _normalize_model_tokens(text: str) -> str:
    """Normalize D5 → D:5 and drop duplicate generation tokens."""
    text = re.sub(r"\bD5\b", "D:5", text)
    if "D:5" not in text:
        return text
    parts: list[str] = []
    seen_d5 = False
    for part in text.split():
        if part == "D:5":
            if seen_d5:
                continue
            seen_d5 = True
        parts.append(part)
    return " ".join(parts)


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


def _apply_ko_maps(text: str) -> str:
    """Replace Korean body/brand/model fragments (longest match first)."""
    if not text:
        return text
    for ko, ru in sorted(KO_BODY_TYPE_MAP.items(), key=lambda kv: len(kv[0]), reverse=True):
        if ko in text:
            text = text.replace(ko, f" {ru} " if ru else " ")
    for ko, ru in sorted(KO_KNOWN_NAMES.items(), key=lambda kv: len(kv[0]), reverse=True):
        if ko in text:
            text = text.replace(ko, ru)
    return text


def _find_canonical_model(text: str) -> str | None:
    for model in _KO_CANONICAL_MODELS:
        if re.search(rf"\b{re.escape(model)}\b", text, re.I):
            return model
    return None


def _reshape_ko_motorhome_title(text: str) -> str:
    """Build Fujicars-like «Автодом ван-кон {Model} …» for /avtodoma/ filter."""
    text = re.sub(r"\s+", " ", text).strip()
    if not text:
        return text

    has_4wd = bool(re.search(r"\b4WD\b", text, re.I))
    model = _find_canonical_model(text)

    # Drop brand prefix and redundant body/Автодом tokens before rebuilding.
    rest = _BRAND_PREFIX_RE.sub("", text)
    rest = re.sub(
        r"\b(?:Автодом|ван-кон|кабина-кон|лёгкий\s+кемпер|автобус-кон|4WD)\b",
        " ",
        rest,
        flags=re.I,
    )
    if model:
        rest = re.sub(rf"\b{re.escape(model)}\b", " ", rest, flags=re.I)
    # Drop leftover Hangul / parenthetical KO fragments (e.g. (삼성)).
    rest = _KO_RE.sub(" ", rest)
    rest = re.sub(r"\([^)]*\)", " ", rest)
    rest = re.sub(r"\s+", " ", rest).strip(" -/," )

    parts = ["Автодом", "ван-кон"]
    if model:
        parts.append(model)
    elif rest:
        parts.append(rest)
        rest = ""
    if has_4wd:
        parts.append("4WD")
    # Keep short Latin/RU extras (package codes), skip brand leftovers.
    if rest and not re.match(
        r"^(Hyundai|Kia|Renault|Samsung|Korea|Mercedes|Chevrolet)\b",
        rest,
        re.I,
    ):
        parts.append(rest)
    return " ".join(parts)


def translate_title_ko(title: str) -> str:
    """Translate an Encar-style Korean motorhome title → RU display form."""
    if not title:
        return ""
    raw = unicodedata.normalize("NFKC", title).strip()
    if not contains_korean(raw):
        # Already Latin/RU — still reshape if it looks like a brand+model camper title.
        if _find_canonical_model(raw) and not raw.lower().startswith("автодом"):
            return _reshape_ko_motorhome_title(raw)
        return raw

    text = _apply_ko_maps(raw)
    text = re.sub(r"\s+", " ", text).strip(" -/,")
    return _reshape_ko_motorhome_title(text) or raw


def translate_title(title: str) -> str:
    """Translate a motorhome title JP/KO → RU."""
    if not title:
        return ""
    stripped = title.strip()
    if contains_korean(stripped):
        return translate_title_ko(stripped)
    if not contains_japanese(stripped):
        # Retranslate already-Latin Encar titles into filter-friendly form.
        if _find_canonical_model(stripped) and not stripped.lower().startswith("автодом"):
            return translate_title_ko(stripped)
        return stripped

    text = normalize_japanese_text(stripped)
    for jp, ru in sorted(BODY_TYPE_MAP.items(), key=lambda kv: len(kv[0]), reverse=True):
        text = text.replace(jp, f" {ru} ")

    for jp, ru in sorted(KNOWN_NAMES.items(), key=lambda kv: len(kv[0]), reverse=True):
        if jp in text:
            text = text.replace(jp, ru)

    text = _apply_kanji_map(text)
    text = _translate_remaining_japanese(text)
    text = _fix_latin_spellings(text)
    text = _apply_kanji_map(text)
    text = _normalize_model_tokens(text)
    text = re.sub(r"\s+", " ", text).strip()
    return text or stripped


def translate_text(text: str | None) -> str | None:
    """Translate common JP/KO terms in free-form spec/equipment strings."""
    if text is None:
        return None
    raw = text.strip()
    if not raw:
        return raw
    if contains_korean(raw):
        result = _apply_ko_maps(unicodedata.normalize("NFKC", raw))
        return re.sub(r"\s+", " ", result).strip() or raw
    if not contains_japanese(raw):
        return raw

    result = normalize_japanese_text(raw)
    for jp, ru in sorted(KNOWN_NAMES.items(), key=lambda kv: len(kv[0]), reverse=True):
        if jp in result:
            result = result.replace(jp, ru)
    result = _apply_kanji_map(result)
    result = _translate_remaining_japanese(result)
    result = _fix_latin_spellings(result)
    result = _apply_kanji_map(result)
    return re.sub(r"\s+", " ", result).strip() or raw


def is_non_grade_value(text: str | None) -> bool:
    """True when text is a body-type/badge label, not an auction assessment."""
    if not text:
        return False
    normalized = unicodedata.normalize("NFKC", text).strip()
    if normalized in _NON_GRADE_VALUES:
        return True
    # Translated camping badges often become «Автодом …».
    if re.fullmatch(r"Автодом(?:\s+4WD)?", normalized, re.I):
        return True
    if contains_korean(normalized):
        for ko in (
            "캠핑카/이동사무차",
            "4WD 캠핑카",
            "캠핑카",
            "특장업체",
            "이동사무차",
        ):
            if ko in normalized and not re.search(r"\d", normalized):
                return True
    return False


def translate_grade(grade: str | None) -> str | None:
    """Translate auction / repair-history grade to Russian display text.

    Returns None for Encar camping badges / body-type labels so «Оценка»
    is hidden in the theme (empty grade is not rendered).
    """
    if grade is None:
        return None
    raw = grade.strip()
    if not raw:
        return None

    if is_non_grade_value(raw):
        return None

    if contains_korean(raw):
        # Remaining KO text is not a JP-style auction score — do not invent «Оценка».
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
    """Apply JP/KO→RU translation to listing title and properties.grade."""
    if listing.source in ("fujicars", "bobaedream", "encar"):
        listing.title = translate_title(listing.title)
        if listing.properties.grade:
            listing.properties.grade = translate_grade(listing.properties.grade)
        # Encar Badge is body type, not auction grade — never keep it in Оценка.
        if listing.source == "encar" and (
            not listing.properties.grade or is_non_grade_value(listing.properties.grade)
        ):
            listing.properties.grade = None
        for param in listing.parameters:
            if param.name in ("Badge", "BadgeDetail") and param.value:
                param.value = translate_text(param.value) or param.value
                continue
            param.value = translate_text(param.value) or param.value
            if param.name in ("Оценка", "修復歴", "Комплектация") and param.value:
                translated = translate_grade(param.value) or translate_text(param.value)
                if translated:
                    param.value = translated
    return listing
