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

export default function RichTextEditor({ value, onChange }) {
  return (
    <CKEditor
      editor={ClassicEditor}
      config={EDITOR_CONFIG}
      data={value ?? ''}
      onChange={(event, editor) => onChange(editor.getData())}
    />
  )
}
