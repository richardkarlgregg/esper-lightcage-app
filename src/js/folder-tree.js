// CSS for folder tree items
const folderTreeStyles = `
    .folder-item {
        opacity: 0.5;
        transition: opacity 0.3s ease;
    }
    .folder-item.active, .folder-item.active .folder-item {
        opacity: 1;
    }
`;

// Append styles to the document
const styleSheet = document.createElement("style");
styleSheet.type = "text/css";
styleSheet.innerText = folderTreeStyles;
document.head.appendChild(styleSheet);

// JavaScript to manage active state
function setActiveFolderItem(item) {
    // Remove active class from all items
    $('.folder-item').removeClass('active');
    // Add active class to the selected item and its children
    $(item).addClass('active').find('.folder-item').addClass('active');
}

// Example usage
$('.folder-item').on('click', function() {
    setActiveFolderItem(this);
}); 