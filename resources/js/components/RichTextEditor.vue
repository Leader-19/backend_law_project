<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import TextAlign from '@tiptap/extension-text-align'
import { TextStyle } from '@tiptap/extension-text-style'
import Color from '@tiptap/extension-color'
import FontFamily from '@tiptap/extension-font-family'
import Underline from '@tiptap/extension-underline'
import Highlight from '@tiptap/extension-highlight'
import { onBeforeUnmount, watch } from 'vue'
import {
    Bold,
    Italic,
    Underline as UnderlineIcon,
    Strikethrough,
    Highlighter,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    List,
    ListOrdered,
    Quote,
    Undo,
    Redo,
    RemoveFormatting,
} from 'lucide-vue-next'

const props = defineProps<{
    modelValue: string
    placeholder?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3, 4],
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        TextStyle,
        Color,
        FontFamily,
        Underline,
        Highlight.configure({
            multicolor: true,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm sm:prose-base max-w-none focus:outline-none min-h-[200px] px-4 py-3 text-sm',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    },
})

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && editor.value.getHTML() !== value) {
            editor.value.commands.setContent(value || '')
        }
    },
)

onBeforeUnmount(() => {
    editor.value?.destroy()
})

const fontSizes = [
    { label: 'Small', value: '12px' },
    { label: 'Normal', value: '16px' },
    { label: 'Medium', value: '18px' },
    { label: 'Large', value: '24px' },
    { label: 'X-Large', value: '32px' },
]

const fontFamilies = [
    { label: 'Default', value: '' },
    { label: 'Arial', value: 'Arial' },
    { label: 'Times New Roman', value: 'Times New Roman' },
    { label: 'Courier New', value: 'Courier New' },
    { label: 'Georgia', value: 'Georgia' },
    { label: 'Verdana', value: 'Verdana' },
]

const colors = [
    '#000000', '#434343', '#666666', '#999999', '#B7B7B7', '#CCCCCC', '#D9D9D9', '#EFEFEF', '#F3F3F3', '#FFFFFF',
    '#980000', '#FF0000', '#FF9900', '#FFFF00', '#00FF00', '#00FFFF', '#4A86E8', '#0000FF', '#9900FF', '#FF00FF',
    '#E6B8AF', '#F4CCCC', '#FCE5CD', '#FFF2CC', '#D9EAD3', '#D0E0E3', '#C9DAF8', '#CFE2F3', '#D9D2E9', '#EAD1DC',
    '#DD7E6B', '#EA9999', '#F9CB9C', '#FFE599', '#B6D7A8', '#A2C4C9', '#A4C2F4', '#9FC5E8', '#B4A7D6', '#D5A6BD',
    '#CC4125', '#E06666', '#F6B26B', '#FFD966', '#93C47D', '#76A5AF', '#6D9EEB', '#6FA8DC', '#8E7CC3', '#C27BA0',
    '#A61C00', '#CC0000', '#E69138', '#F1C232', '#6AA84F', '#45818E', '#3C78D8', '#3D85C6', '#674EA7', '#A64D79',
    '#85200C', '#990000', '#B45F06', '#BF9000', '#38761D', '#134F5C', '#1155CC', '#0B5394', '#351C75', '#741B47',
    '#5B0F00', '#660000', '#783F04', '#7F6000', '#274E13', '#0C343D', '#1C4587', '#073763', '#20124D', '#4C1130',
]

function setFontSize(size: string) {
    editor.value?.chain().focus().setMark('textStyle', { fontSize: size }).run()
}

function setFontFamily(family: string) {
    if (family) {
        editor.value?.chain().focus().setFontFamily(family).run()
    } else {
        editor.value?.chain().focus().unsetFontFamily().run()
    }
}

function setColor(color: string) {
    editor.value?.chain().focus().setColor(color).run()
}
</script>

<template>
    <div v-if="editor" class="border border-gray-300 rounded-lg overflow-hidden bg-white">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 border-b border-gray-200 bg-gray-50">

            <!-- Undo / Redo -->
            <button
                type="button"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed"
                title="Undo"
            >
                <Undo class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                class="p-1.5 rounded hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed"
                title="Redo"
            >
                <Redo class="w-4 h-4" />
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Heading Select -->
            <select
                @change="(e) => {
                    const val = (e.target as HTMLSelectElement).value
                    if (val === 'p') {
                        editor?.chain().focus().setParagraph().run()
                    } else {
                        editor?.chain().focus().toggleHeading({ level: parseInt(val) as 1 | 2 | 3 | 4 }).run()
                    }
                }"
                class="text-xs border border-gray-300 rounded px-1.5 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                title="Heading"
            >
                <option value="p" :selected="!editor.isActive('heading')">Paragraph</option>
                <option value="1" :selected="editor.isActive('heading', { level: 1 })">Heading 1</option>
                <option value="2" :selected="editor.isActive('heading', { level: 2 })">Heading 2</option>
                <option value="3" :selected="editor.isActive('heading', { level: 3 })">Heading 3</option>
                <option value="4" :selected="editor.isActive('heading', { level: 4 })">Heading 4</option>
            </select>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Font Family -->
            <select
                @change="(e) => setFontFamily((e.target as HTMLSelectElement).value)"
                class="text-xs border border-gray-300 rounded px-1.5 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                title="Font Family"
            >
                <option v-for="f in fontFamilies" :key="f.value" :value="f.value">{{ f.label }}</option>
            </select>

            <!-- Font Size -->
            <select
                @change="(e) => setFontSize((e.target as HTMLSelectElement).value)"
                class="text-xs border border-gray-300 rounded px-1.5 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                title="Font Size"
            >
                <option v-for="s in fontSizes" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Bold / Italic / Underline / Strikethrough -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('bold') ? 'bg-blue-100 text-blue-700' : '']"
                title="Bold"
            >
                <Bold class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('italic') ? 'bg-blue-100 text-blue-700' : '']"
                title="Italic"
            >
                <Italic class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('underline') ? 'bg-blue-100 text-blue-700' : '']"
                title="Underline"
            >
                <UnderlineIcon class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('strike') ? 'bg-blue-100 text-blue-700' : '']"
                title="Strikethrough"
            >
                <Strikethrough class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleHighlight({ color: '#FFFF00' }).run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('highlight') ? 'bg-yellow-100 text-yellow-700' : '']"
                title="Highlight"
            >
                <Highlighter class="w-4 h-4" />
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Text Color -->
            <div class="relative group">
                <button
                    type="button"
                    class="p-1.5 rounded hover:bg-gray-200 flex items-center gap-0.5"
                    title="Text Color"
                >
                    <span class="text-xs font-bold">A</span>
                    <span class="w-4 h-1 rounded" :style="{ backgroundColor: editor.getAttributes('textStyle').color || '#000000' }"></span>
                </button>
                <div class="hidden group-hover:block absolute top-full left-0 z-50 mt-1 p-2 bg-white border border-gray-200 rounded-lg shadow-lg w-[220px]">
                    <div class="grid grid-cols-10 gap-0.5">
                        <button
                            v-for="c in colors"
                            :key="c"
                            type="button"
                            @click="setColor(c)"
                            class="w-5 h-5 rounded border border-gray-200 hover:scale-125 transition-transform"
                            :style="{ backgroundColor: c }"
                            :title="c"
                        ></button>
                    </div>
                    <button
                        type="button"
                        @click="editor.chain().focus().unsetColor().run()"
                        class="mt-1.5 text-xs text-gray-500 hover:text-gray-700"
                    >
                        Reset color
                    </button>
                </div>
            </div>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Alignment -->
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('left').run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive({ textAlign: 'left' }) ? 'bg-blue-100 text-blue-700' : '']"
                title="Align Left"
            >
                <AlignLeft class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('center').run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive({ textAlign: 'center' }) ? 'bg-blue-100 text-blue-700' : '']"
                title="Align Center"
            >
                <AlignCenter class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('right').run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive({ textAlign: 'right' }) ? 'bg-blue-100 text-blue-700' : '']"
                title="Align Right"
            >
                <AlignRight class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().setTextAlign('justify').run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive({ textAlign: 'justify' }) ? 'bg-blue-100 text-blue-700' : '']"
                title="Justify"
            >
                <AlignJustify class="w-4 h-4" />
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Lists -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('bulletList') ? 'bg-blue-100 text-blue-700' : '']"
                title="Bullet List"
            >
                <List class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('orderedList') ? 'bg-blue-100 text-blue-700' : '']"
                title="Numbered List"
            >
                <ListOrdered class="w-4 h-4" />
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="['p-1.5 rounded hover:bg-gray-200', editor.isActive('blockquote') ? 'bg-blue-100 text-blue-700' : '']"
                title="Blockquote"
            >
                <Quote class="w-4 h-4" />
            </button>

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <!-- Clear Formatting -->
            <button
                type="button"
                @click="editor.chain().focus().clearNodes().unsetAllMarks().run()"
                class="p-1.5 rounded hover:bg-gray-200"
                title="Clear Formatting"
            >
                <RemoveFormatting class="w-4 h-4" />
            </button>
        </div>

        <!-- Editor Content -->
        <EditorContent :editor="editor" />
    </div>
</template>

<style>
/* Tiptap editor styles */
.tiptap {
    outline: none;
}
.tiptap p.is-editor-empty:first-child::before {
    color: #adb5bd;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}
.tiptap h1 { font-size: 2em; font-weight: bold; margin: 0.5em 0; }
.tiptap h2 { font-size: 1.5em; font-weight: bold; margin: 0.5em 0; }
.tiptap h3 { font-size: 1.25em; font-weight: bold; margin: 0.5em 0; }
.tiptap h4 { font-size: 1.1em; font-weight: bold; margin: 0.5em 0; }
.tiptap blockquote {
    border-left: 3px solid #ccc;
    margin-left: 0;
    padding-left: 1rem;
    color: #666;
}
.tiptap ul, .tiptap ol {
    padding-left: 1.5rem;
    margin: 0.5em 0;
}
.tiptap mark {
    background-color: #fff2cc;
    padding: 0.1em 0.2em;
    border-radius: 2px;
}
</style>
