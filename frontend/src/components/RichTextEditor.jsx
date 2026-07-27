import { useMemo } from 'react'
import { CKEditor } from '@ckeditor/ckeditor5-react'
import {
  Autoformat,
  BlockQuote,
  Bold,
  ClassicEditor,
  Essentials,
  Heading,
  HorizontalLine,
  Italic,
  Link,
  List,
  Paragraph,
  PasteFromOffice,
  SourceEditing,
  Table,
  TableToolbar,
  Undo,
} from 'ckeditor5'
import 'ckeditor5/ckeditor5.css'

const EDITOR_CONFIG = {
  // CKEditor 5 needs a licence key from version 44 onwards. GPL covers the
  // open source build we are using here.
  licenseKey: 'GPL',
  plugins: [
    Autoformat,
    BlockQuote,
    Bold,
    Essentials,
    Heading,
    HorizontalLine,
    Italic,
    Link,
    List,
    Paragraph,
    PasteFromOffice,
    SourceEditing,
    Table,
    TableToolbar,
    Undo,
  ],
  toolbar: [
    'undo',
    'redo',
    '|',
    'heading',
    '|',
    'bold',
    'italic',
    'link',
    '|',
    'bulletedList',
    'numberedList',
    'blockQuote',
    'insertTable',
    'horizontalLine',
    '|',
    'sourceEditing',
  ],
  table: {
    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
  },
  link: {
    addTargetToExternalLinks: true,
  },
}

export default function RichTextEditor({ value, onChange, contentLanguage = 'en' }) {
  // Telling CKEditor the content language is what flips the editing area to
  // right to left; styling it from outside would not move the caret.
  const config = useMemo(
    () => ({ ...EDITOR_CONFIG, language: { ui: 'en', content: contentLanguage } }),
    [contentLanguage],
  )

  return (
    <CKEditor
      key={contentLanguage}
      editor={ClassicEditor}
      config={config}
      data={value ?? ''}
      onChange={(event, editor) => onChange(editor.getData())}
    />
  )
}
