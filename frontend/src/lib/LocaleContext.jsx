import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'

const STORAGE_KEY = 'cms.locale'

export const LOCALES = {
  en: { label: 'English', short: 'EN', dir: 'ltr' },
  ar: { label: 'العربية', short: 'ع', dir: 'rtl' },
}

/** The few bits of interface text the public site owns. Page content is translated in the CMS. */
const STRINGS = {
  en: {
    index: 'Index',
    publishedPages: 'Published pages',
    searchPages: 'Search these pages',
    backOffice: 'Back office',
    page: 'page',
    pages: 'pages',
    section: 'section',
    sections: 'sections',
    nothingPublished: 'Nothing published yet',
    nothingPublishedDetail:
      'Pages appear here once they are published and their publish date has passed.',
    noMatches: 'No pages match that search',
    clearSearch: 'clear the search',
    tryShorter: 'Try a shorter word, or',
    notAvailable: 'Not available',
    notAvailableDetail:
      'This page is not available. It may be a draft, or its publish date may not have arrived yet.',
    backToIndex: 'Back to the index',
    onThisPage: 'On this page',
    previous: 'Previous',
    next: 'Next',
    published: 'Published',
    minRead: 'min read',
    copyLink: 'Copy link',
    linkCopied: 'Link copied',
    loading: 'Loading…',
    onlyInEnglish: 'This page has not been translated yet, so it is shown in English.',
    summary: (pages, sections, locale) =>
      `${pages} ${pages === 1 ? STRINGS[locale].page : STRINGS[locale].pages} across ` +
      `${sections} ${sections === 1 ? STRINGS[locale].section : STRINGS[locale].sections}, ` +
      'in the order the menu sets.',
    matches: (count, term) => `${count} ${count === 1 ? 'match' : 'matches'} for “${term}”`,
  },
  ar: {
    index: 'الفهرس',
    publishedPages: 'الصفحات المنشورة',
    searchPages: 'ابحث في هذه الصفحات',
    backOffice: 'لوحة التحكم',
    page: 'صفحة',
    pages: 'صفحات',
    section: 'قسم',
    sections: 'أقسام',
    nothingPublished: 'لا يوجد محتوى منشور بعد',
    nothingPublishedDetail: 'تظهر الصفحات هنا بعد نشرها وحلول تاريخ النشر.',
    noMatches: 'لا توجد صفحات مطابقة',
    clearSearch: 'امسح البحث',
    tryShorter: 'جرّب كلمة أقصر، أو',
    notAvailable: 'غير متاح',
    notAvailableDetail: 'هذه الصفحة غير متاحة. قد تكون مسودة أو لم يحن تاريخ نشرها بعد.',
    backToIndex: 'العودة إلى الفهرس',
    onThisPage: 'في هذه الصفحة',
    previous: 'السابق',
    next: 'التالي',
    published: 'نُشر في',
    minRead: 'دقيقة قراءة',
    copyLink: 'انسخ الرابط',
    linkCopied: 'تم نسخ الرابط',
    loading: 'جارٍ التحميل…',
    onlyInEnglish: 'لم تُترجم هذه الصفحة بعد، لذا تُعرض بالإنجليزية.',
    summary: (pages, sections) =>
      `${pages} ${pages === 1 ? 'صفحة' : 'صفحات'} في ${sections} ${sections === 1 ? 'قسم' : 'أقسام'}، بترتيب القائمة.`,
    matches: (count, term) => `${count} ${count === 1 ? 'نتيجة' : 'نتائج'} لـ «${term}»`,
  },
}

const LocaleContext = createContext(null)

export function LocaleProvider({ children }) {
  const [locale, setLocale] = useState(() => {
    // ?lang= wins, so a link can be shared in a particular language. Otherwise
    // whatever the visitor last chose.
    const fromUrl = new URLSearchParams(window.location.search).get('lang')

    if (fromUrl && LOCALES[fromUrl]) {
      return fromUrl
    }

    const stored = localStorage.getItem(STORAGE_KEY)

    return stored && LOCALES[stored] ? stored : 'en'
  })

  const dir = LOCALES[locale].dir

  // The document itself has to flip, not just the components: `dir` is what
  // drives every logical CSS property and the browser's own text handling.
  useEffect(() => {
    document.documentElement.lang = locale
    document.documentElement.dir = dir
    localStorage.setItem(STORAGE_KEY, locale)
  }, [locale, dir])

  const value = useMemo(
    () => ({
      locale,
      dir,
      isRtl: dir === 'rtl',
      setLocale,
      t: STRINGS[locale],
    }),
    [locale, dir],
  )

  return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>
}

export function useLocale() {
  const context = useContext(LocaleContext)

  if (!context) {
    throw new Error('useLocale must be used inside a LocaleProvider.')
  }

  return context
}

/** Date formatting follows the chosen language too. */
export function useDateFormatter() {
  const { locale } = useLocale()

  return useCallback(
    (value, options = { day: 'numeric', month: 'short', year: 'numeric' }) => {
      if (!value) return null

      return new Date(value).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-GB', options)
    },
    [locale],
  )
}
