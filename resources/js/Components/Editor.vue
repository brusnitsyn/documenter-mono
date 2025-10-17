<template>
    <div class="editor" ref="editor">

        <!-- Page overlays (headers, footers, page numbers, ...) -->
        <div v-if="overlay" class="overlays" ref="overlays">
            <div v-for="(page, page_idx) in pages" class="overlay" :key="page.uuid+'-overlay'" :ref="(elt) => (pages_overlay_refs[page.uuid] = elt)"
                 v-html="overlay(page_idx+1, pages.length)" :style="page_style(page_idx, false)">
            </div>
        </div>

        <!-- Document editor -->
        <div class="content" ref="content" :contenteditable="editable" :style="page_style(-1)" @input="input" @keyup="e => processElement(e)">
            <!-- This is a Vue "hoisted" static <div> which contains every page of the document and can be modified by the DOM -->
        </div>

        <!-- Items related to the document editor (widgets, ...) can be inserted here -->

    </div>
</template>

<script setup>
import {computed, defineCustomElement, onBeforeUpdate, onMounted, onUnmounted, ref, watch} from 'vue';
import { move_children_forward_recursively, move_children_backwards_with_merging } from '../Utils/pageTransitionMgmt.js';

const props = defineProps({
    // This contains the initial content of the document that can be synced
    // It must be an Array: each array item is a new set of pages containing the
    // item (string or component). You can see that as predefined page breaks.
    // See the Demo.vue file for a good usage example.
    // content: {
    //     type: Array,
    //     required: true
    // },

    // Display mode of the pages
    display: {
        type: String,
        default: "grid" // ["grid", "horizontal", "vertical"]
    },

    // Sets whether document text can be modified
    editable: {
        type: Boolean,
        default: true
    },

    // Overlay function returning page headers and footers in HTML
    overlay: Function,

    // Pages format in mm (should be an array containing [width, height])
    page_format_mm: {
        type: Array,
        default: () => [210, 297]
    },

    // Page margins in CSS
    page_margins: {
        type: [String, Function],
        default: "10mm 15mm"
    },

    // Display zoom. Only acts on the screen display
    zoom: {
        type: Number,
        default: 1.0
    },

    // "Do not break" test function: should return true on elements you don't want to be split over multiple pages but rather be moved to the next page
    do_not_break: Function
})

const emits = defineEmits(['update:content', 'update:current-style', 'update:activeElement'])

const model = defineModel()
const editor = ref()
const content = ref()
const overlays = ref()

const pages = ref([]) // contains {uuid, content_idx, prev_html, template, props, elt} for each pages of the document
const pages_overlay_refs = ref({}) // contains page overlay ref elements indexed by uuid
const pages_height = ref(0) // real measured page height in px (corresponding to page_format_mm[1])
const editor_width = ref(0) // real measured with of an empty editor <div> in px
const prevent_next_content_update_from_parent = ref(false) // workaround to avoid infinite update loop
const current_text_style = ref(false) // contains the style at caret position
const activeElement = defineModel('active-element')
const activeElements = defineModel('active-elements')
const printing_mode = ref(false) // flag set when page is rendering in printing mode
const reset_in_progress = ref(false)
const fit_in_progress = ref(false)
const _page_body = ref()

const css_media_style = computed(() => {
    const style = document.createElement("style");
    document.head.appendChild(style);
    return style;
})

onMounted(() => {
    update_editor_width();
    update_css_media_style();
    reset_content();
    window.addEventListener("resize", update_editor_width);
    window.addEventListener("click", processElement);
    window.addEventListener("beforeprint", before_print);
    window.addEventListener("afterprint", after_print);
})

onBeforeUpdate(() => {
    pages_overlay_refs.value = []
})

onUnmounted(() => {
    window.removeEventListener("resize", update_editor_width);
    window.removeEventListener("click", processElement);
    window.removeEventListener("beforeprint", before_print);
    window.removeEventListener("afterprint", after_print);
})

// Computes a random 5-char UUID
const new_uuid = () => Math.random().toString(36).slice(-5)

// Resets all content from the content property
const reset_content = () => {
    // Prevent launching this function multiple times
    if(reset_in_progress.value) return;
    reset_in_progress.value = true;

    // If provided content is empty, initialize it first and exit
    if(!model.value.length) {
        reset_in_progress.value = false;
        model.value = [""]
        // emits("update:content", [""]);
        return;
    }

    // Delete all pages and set one new page per content item
    pages.value = model.value.map((content, content_idx) => ({
        uuid: new_uuid(),
        content_idx,
        template: content.template,
        props: content.props
    }));
    update_pages_elts();

    // Get page height from first empty page
    const first_page_elt = pages.value[0].elt;
    if(!content.value.contains(first_page_elt)) content.value.appendChild(first_page_elt); // restore page in DOM in case it was removed
    pages_height.value = first_page_elt.clientHeight + 1; // allow one pixel precision

    // Initialize text pages
    for(const page of pages.value) {

        // set raw HTML content
        if(!model.value[page.content_idx]) page.elt.innerHTML = "<div><br></div>"; // ensure empty pages are filled with at least <div><br></div>, otherwise editing fails on Chrome
        else if(typeof model.value[page.content_idx] == "string") page.elt.innerHTML = "<div>"+model.value[page.content_idx]+"</div>";
        else if(page.template) {
            const componentElement = defineCustomElement(page.template);
            customElements.define('component-'+page.uuid, componentElement);
            page.elt.appendChild(new componentElement({ modelValue: page.props }));
        }

        // restore page in DOM in case it was removed
        if(!content.value.contains(page.elt)) content.value.appendChild(page.elt);
    }

    // Spread content over several pages if it overflows
    fit_content_over_pages();

    // Remove the text cursor from the content, if any (its position is lost anyway)
    content.value.blur();

    // Clear "reset in progress" flag
    reset_in_progress.value = false;
}

// Spreads the HTML content over several pages until it fits
const fit_content_over_pages = () => {
    // Data variable pages_height.value must have been set before calling this function
    if(!pages_height.value) return;

    // Prevent launching this function multiple times
    if(fit_in_progress.value) return;
    fit_in_progress.value = true;

    // Check pages that were deleted from the DOM (start from the end)
    for(let page_idx = pages.value.length - 1; page_idx >= 0; page_idx--) {
        const page = pages.value[page_idx];

        // if user deleted the page from the DOM, then remove it from pages.value array
        if(!page.elt || !document.body.contains(page.elt)) pages.value.splice(page_idx, 1);
    }

    // If all the document was wiped out, start a new empty document
    if(!pages.value.length){
        fit_in_progress.value = false; // clear "fit in progress" flag
        model.value = [""]
        // emits("update:content", [""]);
        return;
    }

    // Save current selection (or cursor position) by inserting empty HTML elements at the start and the end of it
    const selection = window.getSelection();
    const start_marker = document.createElement("null");
    const end_marker = document.createElement("null");
    // don't insert markers in case selection fails (if we are editing in components in the shadow-root it selects the page <div> as anchorNode)
    if(selection && selection.rangeCount && selection.anchorNode && !(selection.anchorNode.dataset && selection.anchorNode.dataset.isVDEPage != null)) {
        const range = selection.getRangeAt(0);
        range.insertNode(start_marker);
        range.collapse(false);
        range.insertNode(end_marker);
    }

    // Browse every remaining page
    let prev_page_modified_flag = false;
    for(let page_idx = 0; page_idx < pages.value.length; page_idx++) { // page length can grow inside this loop
        const page = pages.value[page_idx];
        let next_page = pages.value[page_idx + 1];
        let next_page_elt = next_page ? next_page.elt : null;

        // check if this page, the next page, or any previous page content has been modified by the user (don't apply to template pages)
        if(!page.template && (prev_page_modified_flag || page.elt.innerHTML !== page.prev_innerHTML
            || (next_page_elt && !next_page.template && next_page_elt.innerHTML !== next_page.prev_innerHTML))){
            prev_page_modified_flag = true;

            // BACKWARD-PROPAGATION
            // check if content doesn't overflow, and that next page exists and has the same content_idx
            if(page.elt.clientHeight <= pages_height.value && next_page && next_page.content_idx === page.content_idx) {

                // try to append every node from the next page until it doesn't fit
                move_children_backwards_with_merging(page.elt, next_page_elt, () => page.elt.clientHeight > pages_height.value || !next_page_elt.childNodes.length);
            }

            // FORWARD-PROPAGATION
            // check if content overflows
            if(page.elt.clientHeight > pages_height.value) {

                // if there is no next page for the same content, create it
                if(!next_page || next_page.content_idx !== page.content_idx) {
                    next_page = { uuid: new_uuid(), content_idx: page.content_idx };
                    pages.value.splice(page_idx + 1, 0, next_page);
                    update_pages_elts();
                    next_page_elt = next_page.elt;
                }

                console.log(next_page_elt)

                // move the content step by step to the next page, until it fits
                move_children_forward_recursively(page.elt, next_page_elt, () => (page.elt.clientHeight <= pages_height.value), props.do_not_break);
            }

            // CLEANING
            // remove next page if it is empty
            if(next_page_elt && next_page.content_idx === page.content_idx && !next_page_elt.childNodes.length) {
                pages.value.splice(page_idx + 1, 1);
            }
        }

        // update pages in the DOM
        update_pages_elts();
    }

    // Normalize pages HTML content
    for(const page of pages.value) {
        if(!page.template) page.elt.normalize(); // normalize HTML (merge text nodes) - don't touch template pages or it can break Vue
    }

    // Restore selection and remove empty elements
    if(document.body.contains(start_marker)){
        const range = document.createRange();
        range.setStart(start_marker, 0);
        if(document.body.contains(end_marker)) range.setEnd(end_marker, 0);
        selection.removeAllRanges();
        selection.addRange(range);
    }
    if(start_marker.parentElement) start_marker.parentElement.removeChild(start_marker);
    if(end_marker.parentElement) end_marker.parentElement.removeChild(end_marker);

    // Store pages HTML content
    for(const page of pages.value) {
        page.prev_innerHTML = page.elt.innerHTML; // store current pages innerHTML for next call
    }

    // Clear "fit in progress" flag
    fit_in_progress.value = false;
}

// Input event
const input = (e) => {
    if(!e) return; // check that event is set
    fit_content_over_pages(); // fit content according to modifications
    emit_new_content(); // emit content modification
    if(e.inputType !== "insertText") processElement(); // update current style if it has changed
}

// Emit content change to parent
const emit_new_content = () => {
    let removed_pages_flag = false; // flag to call reset_content if some pages were removed by the user

    // process the new content
    const new_content = model.value.map((item, content_idx) => {
        // select pages that correspond to this content item (represented by its index in the array)
        const pgs = pages.value.filter(page => (page.content_idx === content_idx));

        // if there are no pages representing this content (because deleted by the user), mark item as false to remove it
        if(!pgs.length) {
            removed_pages_flag = true;
            return false;
        }
        // if item is a string, concatenate each page content and set that
        else if(typeof item == "string") {
            return pgs.map(page => {
                // remove any useless <div> surrounding the content
                let elt = page.elt;
                while(elt.children.length === 1 && elt.firstChild.tagName && elt.firstChild.tagName.toLowerCase() === "div" && !elt.firstChild.getAttribute("style")) {
                    elt = elt.firstChild;
                }
                return ((elt.innerHTML === "<br>" || elt.innerHTML === "<!---->") ? "" : elt.innerHTML); // treat a page containing a single <br> or an empty comment as an empty content
            }).join('');
        }
        // if item is a component, just clone the item
        else return { template: item.template, props: { ...item.props }};
    }).filter(item => (item !== false)); // remove empty items

    // avoid calling reset_content after the parent content is updated (infinite loop)
    if(!removed_pages_flag) prevent_next_content_update_from_parent.value = true;

    // send event to parent to update the synced content
    model.value = new_content
    // emits("update:content", new_content);
}

// Sets current_text_style with CSS style at caret position
const processElement = (e) => {
    process_current_text_style()
    processCurrentElement(e)
    processSelectedElements(e)
}

const processSelectedElements = () => {
    const selection = window.getSelection()

    const elements = new Set()

    for (let i = 0; i < selection.rangeCount; i++) {
        const range = selection.getRangeAt(i)

        // Получаем общий контейнер выделения
        const commonAncestor = range.commonAncestorContainer

        // Если это текстовый узел, берем его родителя
        if (commonAncestor.nodeType === Node.TEXT_NODE) {
            elements.add(commonAncestor.parentElement)
        } else {
            // Ищем все текстовые узлы в диапазоне
            const treeWalker = document.createTreeWalker(
                commonAncestor,
                NodeFilter.SHOW_TEXT,
                {
                    acceptNode: (node) => {
                        return range.intersectsNode(node) ?
                            NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
                    }
                }
            )

            let textNode
            while (textNode = treeWalker.nextNode()) {
                elements.add(textNode.parentElement)
            }
        }
    }

    const hasEditorParent = []

    for (const element of elements) {
        hasEditorParent.push(checkForEditorParent(element))
    }

    if (hasEditorParent.every(i => i === true) && elements.size > 1) {
        activeElements.value = Array.from(elements)
        return
    }

    activeElements.value = []
}

const process_current_text_style = () => {
    let style = false;
    const sel = window.getSelection();
    if(sel.focusNode) {
        const element = sel.focusNode.tagName ? sel.focusNode : sel.focusNode.parentElement;
        if(element && element.isContentEditable) {
            style = window.getComputedStyle(element);

            // compute additional properties
            style.textDecorationStack = []; // array of text-decoration strings from parent elements
            style.headerLevel = 0;
            style.isList = false;
            let parent = element;
            while(parent){
                const parent_style = window.getComputedStyle(parent);
                // stack CSS text-decoration as it is not overridden by children
                style.textDecorationStack.push(parent_style.textDecoration);
                // check if one parent is a list-item
                if(parent_style.display === "list-item") style.isList = true;
                // get first header level, if any
                if(!style.headerLevel){
                    for(let i = 1; i <= 6; i++){
                        if(parent.tagName.toUpperCase() === "H"+i) {
                            style.headerLevel = i;
                            break;
                        }
                    }
                }
                parent = parent.parentElement;
            }
        }
    }

    emits('update:current-style', style)
    current_text_style.value = style;
}
const processCurrentElement = (e) => {
    let element = false;
    const sel = window.getSelection();
    if(sel.focusNode) {
        element = sel.focusNode.tagName ? sel.focusNode : sel.focusNode.parentElement;
    }

    const hasEditorParent = checkForEditorParent(element)

    // console.log(element)
    if (hasEditorParent) {
        emits('update:activeElement', element)
        activeElement.value = element
    }
}

const checkForEditorParent = (element) => {
    if (!element) return false;

    let currentElement = element;

    // Поднимаемся вверх по DOM дереву и проверяем родителей
    while (currentElement && currentElement !== document.body) {
        // Проверяем классы
        if (currentElement.classList && currentElement.classList.contains('editor')) {
            return true;
        }

        // Проверяем id
        if (currentElement.id === 'editor') {
            return true;
        }

        // Переходим к родительскому элементу
        currentElement = currentElement.parentElement;
    }

    return false;
}

// Process the specific style (position and size) of each page <div> and content <div>
const page_style = (page_idx, allow_overflow) => {
    const px_in_mm = 0.2645833333333;
    const page_width = props.page_format_mm[0] / px_in_mm;
    const page_spacing_mm = 10;
    const page_with_plus_spacing = (page_spacing_mm + props.page_format_mm[0]) * props.zoom / px_in_mm;
    const view_padding = 20;
    const inner_width = editor_width.value - 2 * view_padding;
    let nb_pages_x = 1, page_column, x_pos, x_ofx, left_px, top_mm, bkg_width_mm, bkg_height_mm;
    if(props.display === "horizontal") {
        if(inner_width > (pages.value.length * page_with_plus_spacing)){
            nb_pages_x = Math.floor(inner_width / page_with_plus_spacing);
            left_px = inner_width / (nb_pages_x * 2) * (1 + page_idx * 2) - page_width / 2;
        } else {
            nb_pages_x = pages.value.length;
            left_px = page_with_plus_spacing * page_idx + page_width / 2 * (props.zoom - 1);
        }
        top_mm = 0;
        bkg_width_mm = props.zoom * (props.page_format_mm[0] * nb_pages_x + (nb_pages_x - 1) * page_spacing_mm);
        bkg_height_mm = props.page_format_mm[1] * props.zoom;
    } else { // "grid", vertical
        nb_pages_x = Math.floor(inner_width / page_with_plus_spacing);
        if(nb_pages_x < 1 || props.display === "vertical") nb_pages_x = 1;
        page_column = (page_idx % nb_pages_x);
        x_pos = inner_width / (nb_pages_x * 2) * (1 + page_column * 2) - page_width / 2;
        x_ofx = Math.max(0, (page_width * props.zoom - inner_width) / 2);
        left_px = x_pos + x_ofx;
        top_mm = ((props.page_format_mm[1] + page_spacing_mm) * props.zoom) * Math.floor(page_idx / nb_pages_x);
        const nb_pages_y = Math.ceil(pages.value.length / nb_pages_x);
        bkg_width_mm = props.zoom * (props.page_format_mm[0] * nb_pages_x + (nb_pages_x - 1) * page_spacing_mm);
        bkg_height_mm = props.zoom * (props.page_format_mm[1] * nb_pages_y + (nb_pages_y - 1) * page_spacing_mm);
    }
    if(page_idx >= 0) {
        const style = {
            position: "absolute",
            left: "calc("+ left_px +"px + "+ view_padding +"px)",
            top: "calc("+ top_mm +"mm + "+ view_padding +"px)",
            width: props.page_format_mm[0]+"mm",
            // "height" is set below
            padding: (typeof props.page_margins == "function") ? props.page_margins(page_idx + 1, pages.value.length) : props.page_margins,
            transform: "scale("+ props.zoom +")"
        };
        style[allow_overflow ? "minHeight" : "height"] = props.page_format_mm[1]+"mm";
        return style;
    } else {
        // Content/background <div> is sized so it lets a margin around pages when scrolling at the end
        return { width: "calc("+ bkg_width_mm +"mm + "+ (2*view_padding) +"px)", height: "calc("+ bkg_height_mm +"mm + "+ (2*view_padding) +"px)" };
    }
}

// Utility to convert page_style to CSS string
const css_to_string = (css) => Object.entries(css).map(([k, v]) => k.replace(/[A-Z]/g, match => ("-"+match.toLowerCase()))+":"+v).join(';')

// Update pages <div> from pages.value data
const update_pages_elts = () => {
    // Removing deleted pages
    const deleted_pages = [...content.value.children].filter((page_elt) => !pages.value.find(page => (page.elt === page_elt)));
    for(const page_elt of deleted_pages) { page_elt.remove(); }

    // Adding / updating pages
    for(const [page_idx, page] of pages.value.entries()) {
        // Get either existing page_elt or create it
        if(!page.elt) {
            page.elt = document.createElement("div");
            page.elt.className = "page";
            page.elt.dataset.isVDEPage = "";
            const next_page = pages.value[page_idx + 1];
            content.value.insertBefore(page.elt, next_page ? next_page.elt : null);
        }
        // Update page properties
        page.elt.dataset.contentIdx = page.content_idx;
        if(!printing_mode.value) page.elt.style = Object.entries(page_style(page_idx, page.template ? false : true)).map(([k, v]) => k.replace(/[A-Z]/g, match => ("-"+match.toLowerCase()))+":"+v).join(';'); // (convert page_style to CSS string)
        page.elt.contentEditable = (props.editable && !page.template) ? true : false;
    }
}

// Get and store empty editor <div> width
const update_editor_width = () => {
    editor.value.classList.add("hide_children");
    editor_width.value = editor.value.clientWidth;
    update_pages_elts();
    editor.value.classList.remove("hide_children");
}

const update_css_media_style = () => {
    css_media_style.innerHTML = "@media print { @page { size: "+props.page_format_mm[0]+"mm "+props.page_format_mm[1]+"mm; margin: 0 !important; } .hidden-print { display: none !important; } }";
}

// Prepare content before opening the native print box
const before_print = () => {
    // set the printing mode flag
    printing_mode.value = true;

    console.log('start printing')

    // store the current body aside
    _page_body.value = document.body;

    // create a new body for the print and overwrite CSS
    const print_body = document.createElement("body");
    print_body.style.margin = "0";
    print_body.style.padding = "0";
    print_body.style.background = "white";
    print_body.style.font = window.getComputedStyle(editor.value).font;
    print_body.className = editor.value.className;

    // move each page to the print body
    for(const [page_idx, page] of pages.value.entries()){
        //const page_clone = page_elt.cloneNode(true);
        page.elt.style = ""; // reset page style for the clone
        page.elt.style.position = "relative";
        page.elt.style.padding = (typeof props.page_margins == "function") ? props.page_margins(page_idx + 1, pages.value.length) : props.page_margins;
        page.elt.style.breakBefore = page_idx ? "page" : "auto";
        page.elt.style.width = "calc("+props.page_format_mm[0]+"mm - 2px)";
        page.elt.style.height = "calc("+props.page_format_mm[1]+"mm - 2px)";
        page.elt.style.boxSizing = "border-box";
        page.elt.style.overflow = "hidden";

        // add overlays if any
        const overlay_elt = pages_overlay_refs[page.uuid];
        if(overlay_elt){
            overlay_elt.style.position = "absolute";
            overlay_elt.style.left = "0";
            overlay_elt.style.top = "0";
            overlay_elt.style.transform = "none";
            overlay_elt.style.padding = "0";
            overlay_elt.style.overflow = "hidden";
            page.elt.prepend(overlay_elt);
        }

        print_body.append(page.elt);
    }

    // display a return arrow to let the user restore the original body in case the navigator doesn't call after_print() (it happens sometimes in Chrome)
    // const return_overlay = document.createElement("div");
    // return_overlay.className = "hidden-print"; // css managed in update_css_media_style method
    // return_overlay.style.position = "fixed";
    // return_overlay.style.left = "0";
    // return_overlay.style.top = "0";
    // return_overlay.style.right = "0";
    // return_overlay.style.bottom = "0";
    // return_overlay.style.display = "flex";
    // return_overlay.style.alignItems = "center";
    // return_overlay.style.justifyContent = "center";
    // return_overlay.style.background = "rgba(255, 255, 255, 0.95)";
    // return_overlay.style.cursor = "pointer";
    // return_overlay.innerHTML = '<svg width="220" height="220"><path fill="rgba(0, 0, 0, 0.7)" d="M120.774,179.271v40c47.303,0,85.784-38.482,85.784-85.785c0-47.3-38.481-85.782-85.784-85.782H89.282L108.7,28.286L80.417,0L12.713,67.703l67.703,67.701l28.283-28.284L89.282,87.703h31.492c25.246,0,45.784,20.538,45.784,45.783C166.558,158.73,146.02,179.271,120.774,179.271z"/></svg>'
    // return_overlay.addEventListener("click", after_print);
    // print_body.append(return_overlay);

    // replace current body by the print body
    document.body = print_body;
}

// Restore content after closing the native print box
const after_print = () => {
    // clear the printing mode flag
    printing_mode.value = false;

    // restore pages and overlays
    for(const [page_idx, page] of pages.value.entries()){
        page.elt.style = css_to_string(page_style(page_idx, page.template ? false : true));
        content.value.append(page.elt);
        const overlay_elt = pages_overlay_refs[page.uuid];
        if(overlay_elt) {
            overlay_elt.style = css_to_string(page_style(page_idx, false));
            overlays.value.append(overlay_elt);
        }
    }
    document.body = _page_body.value;

    // recompute editor with and reposition elements
    update_editor_width();
}

watch(model, () => {
    if(prevent_next_content_update_from_parent.value) {
        prevent_next_content_update_from_parent.value = false;
    } else reset_content();
}, {
    deep: true
})

watch(props.display, () => {
    update_pages_elts()
})

watch(props.page_format_mm, () => {
    update_css_media_style()
    reset_content()
})

watch(props.page_margins, () => {
    reset_content()
})

watch(props.zoom, () => {
    update_pages_elts()
})
</script>

<style>
body {
    /* Enable printing of background colors */
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
</style>
<style scoped>
.editor {
    display: block;
    font-family: 'Times New Roman', serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    cursor: default;
}
.editor ::-webkit-scrollbar {
    width: 16px;
    height: 16px;
}
.editor ::-webkit-scrollbar-track,
.editor ::-webkit-scrollbar-corner {
    display: none;
}
.editor ::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.5);
    border: 5px solid transparent;
    border-radius: 16px;
    background-clip: content-box;
}
.editor ::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.8);
}
.editor .hide_children > * {
    display: none;
}
.editor > .content {
    position: relative;
    outline: none;
    margin: 0;
    padding: 0;
    min-width: 100%;
    pointer-events: none;
    color: black;
}
.editor > .content > :deep(.page) {
    position: absolute;
    box-sizing: border-box;
    left: 50%;
    transform-origin: center top;
    background: var(--page-background, white);
    box-shadow: var(--page-box-shadow, 0 1px 3px 1px rgba(60, 64, 67, 0.15));
    border: var(--page-border);
    border-radius: var(--page-border-radius);
    transition: left 0.3s, top 0.3s;
    overflow: hidden;
    pointer-events: all;
}

/* Переменные */
.editor > .content[brs-variable],
.editor > .content :deep(*[brs-variable]) {
    background-color: yellow;
    text-decoration: underline;
    text-decoration-style: dotted;
}

.editor > .content[contenteditable],
.editor > .content :deep(*[contenteditable]) {
    cursor: text;
}
.editor > .content :deep(*[contenteditable=false]) {
    cursor: default;
}
.editor > .overlays {
    position: relative;
    margin: 0;
    padding: 0;
    min-width: 100%;
    pointer-events: none;
}
.editor > .overlays > .overlay {
    position: absolute;
    box-sizing: border-box;
    left: 50%;
    transform-origin: center top;
    transition: left 0.3s, top 0.3s;
    overflow: hidden;
    z-index: 1;
}
</style>
