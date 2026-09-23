

import swal from "sweetalert2";
import DOMPurify from "dompurify";
import Quill from "quill";
import "quill/dist/quill.snow.css";

window.Swal = swal;

document.querySelectorAll(".wysiwyg-answer").forEach((field) => {
    const editor = document.querySelector(field.dataset.editor);

    if (!editor) {
        return;
    }

    const quill = new Quill(editor, {
        theme: "snow",
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ["bold", "italic", "underline"],
                [{ list: "ordered" }, { list: "bullet" }],
                ["link"],
                ["clean"],
            ],
        },
    });

    quill.clipboard.dangerouslyPasteHTML(DOMPurify.sanitize(field.value));
    quill.on("text-change", () => {
        field.value = quill.root.innerHTML;
    });
});
