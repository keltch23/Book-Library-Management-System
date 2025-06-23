// Mock data
const mockRequests = [
    {
        id: '1',
        from: 'Sarah Johnson',
        fromEmail: 'sarah.johnson@university.edu',
        subject: 'Book Request: Advanced Machine Learning',
        preview: 'Hi, I would like to request the book "Advanced Machine Learning" by Christopher Bishop for my research project...',
        content: `Hi Library Team,

I would like to request the book "Advanced Machine Learning" by Christopher Bishop for my research project on neural networks. I need this book for my thesis work and would appreciate if you could reserve it for me.

The book details:
- Title: Advanced Machine Learning
- Author: Christopher Bishop
- ISBN: 978-0387310732
- Publisher: Springer

I am available to pick it up any time this week. Please let me know when it becomes available.

Thank you for your assistance.

Best regards,
Sarah Johnson
PhD Student, Computer Science Department
sarah.johnson@university.edu`,
        date: 'Jun 20',
        time: '2:30 PM',
        starred: true,
        read: false,
        priority: 'high',
        category: 'book-request'
    },
    {
        id: '2',
        from: 'Michael Chen',
        fromEmail: 'michael.chen@university.edu',
        subject: 'Renewal Request: Data Structures and Algorithms',
        preview: 'Dear Library Staff, I would like to request a renewal for the book "Data Structures and Algorithms" that I borrowed...',
        content: `Dear Library Staff,

I would like to request a renewal for the book "Data Structures and Algorithms" by Thomas Cormen that I borrowed last month. I still need it for my coursework and would appreciate an extension.

Current due date: June 28, 2024
Requested extension: 2 weeks

I understand there might be a waiting list, but I would be grateful if you could accommodate this request as I'm still actively using the book for my assignments.

Thank you for your consideration.

Best regards,
Michael Chen
Computer Science Student
michael.chen@university.edu`,
        date: 'Jun 18',
        time: '10:15 AM',
        read: true,
        priority: 'normal',
        category: 'renewal'
    },
    {
        id: '3',
        from: 'Emily Davis',
        fromEmail: 'emily.davis@university.edu',
        subject: 'Inquiry: Library Hours During Summer',
        preview: 'Hello, I wanted to inquire about the library operating hours during the summer semester...',
        content: `Hello,

I wanted to inquire about the library operating hours during the summer semester. I'm taking summer courses and need to know when I can access the library resources.

Specifically, I need to know:
1. Regular weekday hours
2. Weekend availability
3. Any holiday closures in July
4. Study room booking procedures

I would also like to know if there are any changes to the book borrowing policies during summer.

Thank you for your time.

Best regards,
Emily Davis
Literature Student
emily.davis@university.edu`,
        date: 'Jun 16',
        time: '4:45 PM',
        read: true,
        priority: 'normal',
        category: 'inquiry'
    },
    {
        id: '4',
        from: 'David Wilson',
        fromEmail: 'david.wilson@university.edu',
        subject: 'Book Request: Modern Physics Textbook',
        preview: 'Hi there, I am looking for a comprehensive modern physics textbook for my graduate studies...',
        content: `Hi there,

I am looking for a comprehensive modern physics textbook for my graduate studies. I've heard great things about "Modern Physics" by Richard Feynman and would like to request it.

I'm particularly interested in the chapters covering:
- Quantum mechanics fundamentals
- Particle physics
- Relativity theory

If this book is not available, could you please suggest similar alternatives? I'm flexible with the author as long as the content covers advanced physics concepts.

My student ID is: 2024-PHY-001

Thank you for your help!

Best regards,
David Wilson
Physics Graduate Student
david.wilson@university.edu`,
        date: 'Jun 15',
        time: '11:20 AM',
        read: false,
        priority: 'normal',
        category: 'book-request'
    },
    {
        id: '5',
        from: 'Lisa Anderson',
        fromEmail: 'lisa.anderson@university.edu',
        subject: 'Issue: Damaged Book Return',
        preview: 'Dear Library Team, I need to report that the book I borrowed has been accidentally damaged...',
        content: `Dear Library Team,

I need to report that the book I borrowed has been accidentally damaged and I want to address this issue promptly.

Book Details:
- Title: Introduction to Psychology
- Author: David Myers
- Borrowed on: May 15, 2024
- Due date: June 15, 2024

The damage occurred when I spilled coffee on pages 45-47. The text is still readable but the pages are stained. I take full responsibility for this accident and am willing to pay for repairs or replacement.

Could you please let me know:
1. The replacement cost
2. Payment procedures
3. Whether I can still return the book or if I should keep it

I apologize for this incident and appreciate your understanding.

Sincerely,
Lisa Anderson
Psychology Student
lisa.anderson@university.edu`,
        date: 'Jun 14',
        time: '9:30 AM',
        read: true,
        priority: 'high',
        category: 'complaint'
    }
];

const mockBooks = [
    {
        id: '1',
        title: 'The Great Gatsby',
        author: 'F. Scott Fitzgerald',
        category: 'Fiction',
        dueDate: 'Jul 15',
        status: 'borrowed',
        description: 'A classic American novel set in the Jazz Age, exploring themes of wealth, love, and the American Dream.',
        starred: true,
        coverUrl: 'https://images.pexels.com/photos/1370295/pexels-photo-1370295.jpeg?auto=compress&cs=tinysrgb&w=150'
    },
    {
        id: '2',
        title: 'Combined Science',
        author: 'Kelvin Nkoma',
        category: 'Science',
        status: 'available',
        description: 'Comprehensive science textbook covering multiple disciplines including physics, chemistry, and biology.',
        coverUrl: 'https://images.pexels.com/photos/256541/pexels-photo-256541.jpeg?auto=compress&cs=tinysrgb&w=150'
    },
    {
        id: '3',
        title: 'Data Structures and Algorithms',
        author: 'Thomas Cormen',
        category: 'Computer Science',
        dueDate: 'Jun 28',
        status: 'overdue',
        description: 'Essential algorithms and data structures for programming, covering fundamental computer science concepts.',
        coverUrl: 'https://images.pexels.com/photos/1181244/pexels-photo-1181244.jpeg?auto=compress&cs=tinysrgb&w=150'
    },
    {
        id: '4',
        title: 'Modern Physics',
        author: 'Richard Feynman',
        category: 'Physics',
        status: 'requested',
        description: 'Advanced concepts in modern physics and quantum mechanics by Nobel laureate Richard Feynman.',
        coverUrl: 'https://images.pexels.com/photos/1181677/pexels-photo-1181677.jpeg?auto=compress&cs=tinysrgb&w=150'
    },
    {
        id: '5',
        title: 'Web Development Fundamentals',
        author: 'Sarah Johnson',
        category: 'Technology',
        status: 'available',
        description: 'Complete guide to modern web development covering HTML, CSS, JavaScript, and popular frameworks.',
        coverUrl: 'https://images.pexels.com/photos/1181244/pexels-photo-1181244.jpeg?auto=compress&cs=tinysrgb&w=150'
    }
];

// Global state
let currentSection = 'inbox';
let currentTab = 'primary';
let selectedRequest = null;
let selectedBook = null;
let sidebarCollapsed = false;
let isMobile = window.innerWidth <= 768;

// Initialize the app
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    renderRequests();
    renderBooks();
    setActiveSection('inbox');
    updateCounts();
    
    // Handle window resize
    window.addEventListener('resize', function() {
        isMobile = window.innerWidth <= 768;
        if (!isMobile) {
            closeMobileMenus();
        }
    });
    
    // Handle clicks outside compose modal
    document.getElementById('compose-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCompose();
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideCompose();
            closeMobileMenus();
        }
    });
});

// Sidebar functions
function toggleSidebar() {
    if (isMobile) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    } else {
        const sidebar = document.getElementById('sidebar');
        sidebarCollapsed = !sidebarCollapsed;
        sidebar.classList.toggle('collapsed', sidebarCollapsed);
    }
}

function setActiveSection(section) {
    currentSection = section;
    
    // Update sidebar
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeNavItem = document.querySelector(`[data-section="${section}"]`);
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }
    
    // Update views
    document.querySelectorAll('.view').forEach(view => {
        view.classList.remove('active');
    });
    
    if (section === 'inbox') {
        document.getElementById('inbox-view').classList.add('active');
        currentTab = 'primary';
        renderRequests();
        updateSearchPlaceholder('Search requests, subjects, senders...');
    } else {
        document.getElementById('books-view').classList.add('active');
        currentTab = 'all';
        renderBooks();
        updateSearchPlaceholder('Search books, authors, categories...');
    }
    
    // Update active tab
    updateActiveTabs();
    
    // Clear selections
    selectedRequest = null;
    selectedBook = null;
    renderRequestDetail();
    renderBookDetail();
    
    // Close mobile sidebar
    if (isMobile) {
        closeMobileMenus();
    }
}

function setActiveTab(tab) {
    currentTab = tab;
    updateActiveTabs();
    
    // Re-render content
    if (currentSection === 'inbox') {
        renderRequests();
    } else {
        renderBooks();
    }
}

function updateActiveTabs() {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(tabBtn => {
        tabBtn.classList.remove('active');
    });
    const activeTab = document.querySelector(`[data-tab="${currentTab}"]`);
    if (activeTab) {
        activeTab.classList.add('active');
    }
}

function updateSearchPlaceholder(placeholder) {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.placeholder = placeholder;
    }
}

function updateCounts() {
    // Update request count
    const filteredRequests = getFilteredRequests();
    const requestCount = document.getElementById('request-count');
    if (requestCount) {
        requestCount.textContent = `1-${filteredRequests.length} of ${mockRequests.length}`;
    }
    
    // Update book count
    const filteredBooks = getFilteredBooks();
    const bookCount = document.getElementById('book-count');
    if (bookCount) {
        bookCount.textContent = `1-${filteredBooks.length} of ${mockBooks.length}`;
    }
}

// Request functions
function renderRequests() {
    const requestItems = document.getElementById('request-items');
    const filteredRequests = getFilteredRequests();
    
    if (!requestItems) return;
    
    requestItems.innerHTML = filteredRequests.map(request => `
        <div class="request-item ${request.read ? '' : 'unread'} ${request.priority === 'high' ? 'high-priority' : request.priority === 'low' ? 'low-priority' : ''} ${selectedRequest && selectedRequest.id === request.id ? 'selected' : ''}" 
             onclick="selectRequest('${request.id}')">
            <input type="checkbox" class="request-checkbox" onclick="event.stopPropagation()">
            <button class="star-btn ${request.starred ? 'starred' : ''}" onclick="event.stopPropagation(); toggleRequestStar('${request.id}')">
                <i data-lucide="star"></i>
            </button>
            <div class="request-content">
                <div class="request-header">
                    <div class="request-sender">
                        <span>${request.from}</span>
                        ${getCategoryIcon(request.category)}
                    </div>
                    <div class="request-meta">
                        ${request.priority === 'high' ? '<span class="priority-badge">High Priority</span>' : ''}
                        <span>${request.date}</span>
                        <span>${request.time}</span>
                    </div>
                </div>
                <div class="request-subject">${request.subject}</div>
                <div class="request-preview">${request.preview}</div>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
    updateCounts();
}

function getFilteredRequests() {
    let filtered = mockRequests;
    
    if (currentSection === 'starred') {
        filtered = filtered.filter(request => request.starred);
    }
    
    if (currentSection === 'inbox') {
        switch (currentTab) {
            case 'book-requests':
                filtered = filtered.filter(request => request.category === 'book-request');
                break;
            case 'renewals':
                filtered = filtered.filter(request => request.category === 'renewal');
                break;
            case 'inquiries':
                filtered = filtered.filter(request => request.category === 'inquiry' || request.category === 'complaint');
                break;
        }
    }
    
    return filtered;
}

function getCategoryIcon(category) {
    const icons = {
        'book-request': '<i data-lucide="book-open" class="category-icon" style="color: #1a73e8;"></i>',
        'renewal': '<i data-lucide="rotate-ccw" class="category-icon" style="color: #34a853;"></i>',
        'inquiry': '<i data-lucide="help-circle" class="category-icon" style="color: #9334e6;"></i>',
        'complaint': '<i data-lucide="alert-circle" class="category-icon" style="color: #ea4335;"></i>'
    };
    return icons[category] || '';
}

function selectRequest(requestId) {
    selectedRequest = mockRequests.find(request => request.id === requestId);
    
    if (selectedRequest) {
        // Mark as read
        selectedRequest.read = true;
        
        // Update UI
        renderRequests();
        renderRequestDetail();
        
        // Show detail panel on mobile
        if (isMobile) {
            const detailPanel = document.getElementById('request-detail');
            const overlay = document.getElementById('mobile-overlay');
            detailPanel.classList.add('open');
            overlay.classList.add('active');
        }
    }
}

function renderRequestDetail() {
    const requestDetail = document.getElementById('request-detail');
    
    if (!requestDetail) return;
    
    if (!selectedRequest) {
        requestDetail.innerHTML = `
            <div class="no-selection">
                <i data-lucide="mail" class="no-selection-icon"></i>
                <p class="no-selection-title">No request selected</p>
                <p class="no-selection-subtitle">Select a request to view details</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    requestDetail.innerHTML = `
        <div class="request-detail-content">
            <div class="request-detail-header">
                <div class="request-detail-title">${selectedRequest.subject}</div>
                <div class="request-detail-from">
                    <strong>${selectedRequest.from}</strong> &lt;${selectedRequest.fromEmail}&gt;
                </div>
                <div class="request-detail-date">
                    ${selectedRequest.date} at ${selectedRequest.time}
                    ${selectedRequest.priority === 'high' ? '<span class="priority-badge">High Priority</span>' : ''}
                </div>
            </div>
            <div class="request-detail-body">
                <div class="request-detail-text">${selectedRequest.content}</div>
            </div>
            <div class="request-detail-actions">
                <div class="action-buttons">
                    <button class="action-btn" onclick="replyToRequest()">
                        <i data-lucide="reply"></i>
                        Reply
                    </button>
                    <button class="action-btn secondary" onclick="forwardRequest()">
                        <i data-lucide="forward"></i>
                    </button>
                    <button class="action-btn secondary" onclick="archiveRequest()">
                        <i data-lucide="archive"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    lucide.createIcons();
}

function toggleRequestStar(requestId) {
    const request = mockRequests.find(r => r.id === requestId);
    if (request) {
        request.starred = !request.starred;
        renderRequests();
        if (selectedRequest && selectedRequest.id === requestId) {
            selectedRequest.starred = request.starred;
        }
    }
}

// Book functions
function renderBooks() {
    const bookItems = document.getElementById('book-items');
    const filteredBooks = getFilteredBooks();
    
    if (!bookItems) return;
    
    bookItems.innerHTML = filteredBooks.map(book => `
        <div class="book-item ${book.status === 'overdue' ? 'overdue' : ''} ${selectedBook && selectedBook.id === book.id ? 'selected' : ''}" onclick="selectBook('${book.id}')">
            <input type="checkbox" class="book-checkbox" onclick="event.stopPropagation()">
            <button class="star-btn ${book.starred ? 'starred' : ''}" onclick="event.stopPropagation(); toggleBookStar('${book.id}')">
                <i data-lucide="star"></i>
            </button>
            ${book.coverUrl ? `<img src="${book.coverUrl}" alt="${book.title}" class="book-cover">` : ''}
            <div class="book-info">
                <div class="book-title-row">
                    <span class="book-title">${book.title}</span>
                    <div class="book-status ${book.status}">
                        ${getStatusIcon(book.status)}
                        <span>${book.status}</span>
                    </div>
                </div>
                <div class="book-author">by ${book.author}</div>
                <div class="book-description">${book.description}</div>
            </div>
            <div class="book-meta">
                <div class="book-category">${book.category}</div>
                ${book.dueDate ? `<div class="book-due ${book.status === 'overdue' ? 'overdue' : ''}">Due ${book.dueDate}</div>` : ''}
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
    updateCounts();
}

function getFilteredBooks() {
    let filtered = mockBooks;
    
    switch (currentSection) {
        case 'my-books':
            filtered = filtered.filter(book => book.status === 'borrowed' || book.status === 'overdue');
            break;
        case 'starred':
            filtered = filtered.filter(book => book.starred);
            break;
        case 'requested':
            filtered = filtered.filter(book => book.status === 'requested');
            break;
        case 'overdue':
            filtered = filtered.filter(book => book.status === 'overdue');
            break;
        case 'returned':
            filtered = [];
            break;
    }
    
    return filtered;
}

function getStatusIcon(status) {
    const icons = {
        'available': '<i data-lucide="check-circle"></i>',
        'borrowed': '<i data-lucide="book-open"></i>',
        'overdue': '<i data-lucide="alert-circle"></i>',
        'requested': '<i data-lucide="clock"></i>'
    };
    return icons[status] || '';
}

function selectBook(bookId) {
    selectedBook = mockBooks.find(book => book.id === bookId);
    
    if (selectedBook) {
        // Update UI
        renderBooks();
        renderBookDetail();
        
        // Show detail panel on mobile
        if (isMobile) {
            const detailPanel = document.getElementById('book-detail');
            const overlay = document.getElementById('mobile-overlay');
            detailPanel.classList.add('open');
            overlay.classList.add('active');
        }
    }
}

function renderBookDetail() {
    const bookDetail = document.getElementById('book-detail');
    
    if (!bookDetail) return;
    
    if (!selectedBook) {
        bookDetail.innerHTML = `
            <div class="no-selection">
                <i data-lucide="book-open" class="no-selection-icon"></i>
                <p class="no-selection-title">No book selected</p>
                <p class="no-selection-subtitle">Select a book to view details</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    bookDetail.innerHTML = `
        <div class="book-detail-content">
            ${selectedBook.coverUrl ? `<img src="${selectedBook.coverUrl}" alt="${selectedBook.title}" class="book-detail-cover">` : ''}
            <div class="book-detail-title">${selectedBook.title}</div>
            <div class="book-detail-author">by ${selectedBook.author}</div>
            <div class="book-detail-status ${selectedBook.status}">
                ${getStatusIcon(selectedBook.status)}
                <span>${selectedBook.status}</span>
            </div>
            
            <div class="book-detail-description">
                <h3>Description</h3>
                <p>${selectedBook.description}</p>
            </div>
            
            <div class="book-detail-info">
                <h3>Details</h3>
                <dl>
                    <dt>Category:</dt>
                    <dd>${selectedBook.category}</dd>
                    ${selectedBook.dueDate ? `
                        <dt>Due Date:</dt>
                        <dd class="${selectedBook.status === 'overdue' ? 'overdue' : ''}">${selectedBook.dueDate}</dd>
                    ` : ''}
                </dl>
            </div>
            
            <div class="book-actions">
                ${getBookActionButtons(selectedBook.status)}
                <button class="book-action-btn secondary" onclick="addToWishlist('${selectedBook.id}')">Add to Wishlist</button>
            </div>
        </div>
    `;
    
    lucide.createIcons();
}

function getBookActionButtons(status) {
    switch (status) {
        case 'available':
            return '<button class="book-action-btn primary" onclick="borrowBook()">Borrow Book</button>';
        case 'borrowed':
            return '<button class="book-action-btn success" onclick="returnBook()">Return Book</button>';
        case 'overdue':
            return '<button class="book-action-btn primary" onclick="returnBook()">Return Overdue Book</button>';
        default:
            return '';
    }
}

function toggleBookStar(bookId) {
    const book = mockBooks.find(b => b.id === bookId);
    if (book) {
        book.starred = !book.starred;
        renderBooks();
        if (selectedBook && selectedBook.id === bookId) {
            selectedBook.starred = book.starred;
        }
    }
}

// Compose functions
function showCompose() {
    document.getElementById('compose-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideCompose() {
    document.getElementById('compose-modal').classList.remove('active');
    document.body.style.overflow = '';
    // Reset form
    document.getElementById('compose-subject').value = '';
    document.getElementById('compose-message').value = '';
}

function handleComposeSubmit(event) {
    event.preventDefault();
    
    const subject = document.getElementById('compose-subject').value;
    const message = document.getElementById('compose-message').value;
    
    if (subject && message) {
        // Simulate sending
        alert('Request sent successfully!');
        hideCompose();
    }
}

// Mobile functions
function closeMobileMenus() {
    const sidebar = document.getElementById('sidebar');
    const requestDetail = document.getElementById('request-detail');
    const bookDetail = document.getElementById('book-detail');
    const overlay = document.getElementById('mobile-overlay');
    
    sidebar.classList.remove('open');
    requestDetail.classList.remove('open');
    bookDetail.classList.remove('open');
    overlay.classList.remove('active');
}

// Action functions
function replyToRequest() {
    showCompose();
    if (selectedRequest) {
        document.getElementById('compose-subject').value = `Re: ${selectedRequest.subject}`;
    }
}

function forwardRequest() {
    alert('Forward functionality would be implemented here');
}

function archiveRequest() {
    alert('Archive functionality would be implemented here');
}

function borrowBook() {
    if (selectedBook) {
        selectedBook.status = 'borrowed';
        selectedBook.dueDate = 'Aug 15';
        renderBooks();
        renderBookDetail();
        alert(`"${selectedBook.title}" has been borrowed successfully!`);
    }
}

function returnBook() {
    if (selectedBook) {
        selectedBook.status = 'available';
        selectedBook.dueDate = null;
        renderBooks();
        renderBookDetail();
        alert(`"${selectedBook.title}" has been returned successfully!`);
    }
}

function addToWishlist(bookId) {
    alert('Book added to wishlist!');
}