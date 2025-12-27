// Homepage JavaScript - Index Page Functions

// Helper function to get correct AJAX path
function getAjaxPath() {
    return window.location.pathname.includes('/pages/') ? '../includes/ajax.php' : 'includes/ajax.php';
}

// Global variables
let currentPostId = null;
let scrollToComments = false;

// Tab switching
function switchTab(tabName, event) {
    document.querySelectorAll('.tab-content').forEach((tab) => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach((btn) => {
        btn.classList.remove('active');
    });
    document.getElementById('tab-' + tabName).classList.add('active');
    if (event && event.target) {
        event.target.classList.add('active');
    }

    // Reset search when switching tabs
    document.getElementById('searchInput').value = '';
    searchPosts('');
}

// Search posts function
function searchPosts(query) {
    query = query.toLowerCase().trim();

    // Get active tab
    const activeTab = document.querySelector('.tab-content.active');
    const postCards = activeTab.querySelectorAll('.post-card');

    let visibleCount = 0;
    postCards.forEach((card) => {
        const title = card.querySelector('.post-title')?.textContent.toLowerCase() || '';
        const excerpt = card.querySelector('.post-excerpt')?.textContent.toLowerCase() || '';
        const tags = card.querySelector('.post-tags')?.textContent.toLowerCase() || '';
        const author = card.querySelector('.author-name')?.textContent.toLowerCase() || '';

        const matches = title.includes(query) || excerpt.includes(query) || tags.includes(query) || author.includes(query);

        if (matches || query === '') {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show "no results" message if needed
    let noResultsMsg = activeTab.querySelector('.no-results-message');
    if (visibleCount === 0 && query !== '') {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'no-results-message';
            noResultsMsg.style.cssText = 'text-align: center; padding: 3rem; color: #636e72;';
            noResultsMsg.innerHTML =
                '<div style="font-size: 3rem;">🔍</div><p>Không tìm thấy bài viết nào phù hợp với "<strong>' + query + '</strong>"</p>';
            activeTab.querySelector('.posts-grid').after(noResultsMsg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Toggle like
function toggleLike(postId, button) {
    fetch(getAjaxPath(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_like&post_id=' + postId,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                button.classList.toggle('liked');
                button.querySelector('.like-count').textContent = data.like_count;
            }
        });
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toggle reply form visibility (for modal comments)
window.toggleReplyForm = function (commentId) {
    const form = document.getElementById('reply-form-' + commentId);
    if (form) {
        const isShowing = form.style.display === 'none';
        form.style.display = isShowing ? 'block' : 'none';

        // Khởi tạo mention autocomplete khi form được hiển thị
        if (isShowing && typeof initMentionAutocomplete === 'function') {
            const textarea = form.querySelector('textarea');
            if (textarea) {
                console.log('Initializing mention for reply textarea:', textarea.id);
                initMentionAutocomplete(textarea);
            }
        }
    }
};

// Submit reply with AJAX (for modal comments)
window.submitReply = function (event, commentId, postId) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');

    // Disable submit button
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang gửi...';
    }

    fetch('includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body:
            'action=add_comment&post_id=' +
            postId +
            '&comment_content=' +
            encodeURIComponent(formData.get('comment_content')) +
            '&parent_id=' +
            formData.get('parent_id'),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Add new comment to DOM without reloading
                const repliesContainer = document.getElementById('replies-' + formData.get('parent_id'));
                if (repliesContainer) {
                    const newComment = document.createElement('div');
                    newComment.className = 'comment reply';
                    newComment.id = 'comment-' + data.comment.id;

                    const timeAgo = 'Vừa xong';

                    newComment.innerHTML = `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <strong style="color: var(--primary-mint);">↳ ${escapeHtml(data.comment.ho_ten)}</strong>
                            <span style="color: #636e72; font-size: 0.85rem;">${timeAgo}</span>
                        </div>
                        <div>${escapeHtml(data.comment.content).replace(/\n/g, '<br>')}</div>
                    `;

                    repliesContainer.appendChild(newComment);

                    // Scroll to new comment smoothly
                    newComment.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                    });

                    // Highlight new comment briefly
                    newComment.style.backgroundColor = '#e8f8f5';
                    setTimeout(() => {
                        newComment.style.transition = 'background-color 1s';
                        newComment.style.backgroundColor = 'transparent';
                    }, 2000);
                }

                // Clear form but keep it open for continuous replies
                form.reset();
                // Focus back to textarea for easy continuous replies
                const textarea = form.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                }
            } else {
                alert(data.message || 'Có lỗi xảy ra khi gửi bình luận');
            }

            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Gửi';
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra');

            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Gửi';
            }
        });

    return false;
};

// Mention autocomplete functionality
window.initMentionAutocomplete = function (textarea) {
    if (!textarea) {
        console.error('Textarea not found for mention autocomplete');
        return;
    }
    console.log('Initializing mention autocomplete for:', textarea.id || textarea);
    let mentionList = null;
    let currentMentionSearch = '';
    let mentionStartPos = -1;

    textarea.addEventListener('input', function (e) {
        console.log('Input event fired! Value:', this.value);
        const text = this.value;
        const cursorPos = this.selectionStart;

        // Find @ symbol before cursor
        let atPos = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (text[i] === '@') {
                // Check if @ is at start or after whitespace
                if (i === 0 || /\s/.test(text[i - 1])) {
                    atPos = i;
                    break;
                }
            }
            if (/\s/.test(text[i])) break;
        }

        if (atPos !== -1) {
            mentionStartPos = atPos;
            currentMentionSearch = text.substring(atPos + 1, cursorPos);
            console.log('Found @ at position:', atPos, 'search term:', currentMentionSearch);

            if (currentMentionSearch.length >= 0) {
                searchUsers(currentMentionSearch, textarea);
            } else {
                hideMentionList();
            }
        } else {
            hideMentionList();
        }
    });

    textarea.addEventListener('keydown', function (e) {
        if (mentionList && mentionList.style.display !== 'none') {
            const items = mentionList.querySelectorAll('.mention-item');
            const selected = mentionList.querySelector('.mention-item.selected');
            let selectedIndex = Array.from(items).indexOf(selected);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelection(items, selectedIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
                updateSelection(items, selectedIndex);
            } else if (e.key === 'Enter' && selected) {
                e.preventDefault();
                selectMention(selected.dataset.username, textarea);
            } else if (e.key === 'Escape') {
                hideMentionList();
            }
        }
    });

    function searchUsers(query, textarea) {
        console.log('Searching users with query:', query);

        fetch(getAjaxPath() + '?action=search_users&q=' + encodeURIComponent(query))
            .then((response) => response.json())
            .then((data) => {
                console.log('Search response:', data);
                if (data.success && data.users.length > 0) {
                    showMentionList(data.users, textarea);
                } else {
                    hideMentionList();
                }
            })
            .catch((error) => {
                console.error('Mention search error:', error);
            });
    }

    function showMentionList(users, textarea) {
        if (!mentionList) {
            mentionList = document.createElement('div');
            mentionList.className = 'mention-autocomplete';
            document.body.appendChild(mentionList);
        }

        mentionList.innerHTML = '';
        users.forEach((user, index) => {
            const item = document.createElement('div');
            item.className = 'mention-item' + (index === 0 ? ' selected' : '');
            item.dataset.username = user.username;
            item.innerHTML = `
                <strong>@${user.username}</strong>
                <span>${user.ho_ten}</span>
            `;
            item.onclick = () => selectMention(user.username, textarea);
            mentionList.appendChild(item);
        });

        // Position the list
        const rect = textarea.getBoundingClientRect();
        mentionList.style.left = rect.left + 'px';
        mentionList.style.top = rect.bottom + window.scrollY + 'px';
        mentionList.style.width = rect.width + 'px';
        mentionList.style.display = 'block';
    }

    function hideMentionList() {
        if (mentionList) {
            mentionList.style.display = 'none';
        }
    }

    function updateSelection(items, index) {
        items.forEach((item) => item.classList.remove('selected'));
        if (items[index]) {
            items[index].classList.add('selected');
            items[index].scrollIntoView({
                block: 'nearest',
            });
        }
    }

    function selectMention(username, textarea) {
        const text = textarea.value;
        const before = text.substring(0, mentionStartPos);
        const after = text.substring(textarea.selectionStart);
        textarea.value = before + '@' + username + ' ' + after;
        textarea.selectionStart = textarea.selectionEnd = before.length + username.length + 2;
        hideMentionList();
        textarea.focus();
    }

    // Close on click outside
    document.addEventListener('click', function (e) {
        if (mentionList && !textarea.contains(e.target) && !mentionList.contains(e.target)) {
            hideMentionList();
        }
    });
};

// Like post in modal
window.likePostInModal = function (postId, button) {
    fetch(getAjaxPath(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_like&post_id=' + postId,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                button.classList.toggle('liked');
                button.querySelector('.like-count-modal').textContent = data.like_count;

                // Update count in main page too
                const mainPageButton = document.querySelector(`[onclick*="toggleLike(${postId}"]`);
                if (mainPageButton) {
                    if (data.liked) {
                        mainPageButton.classList.add('liked');
                    } else {
                        mainPageButton.classList.remove('liked');
                    }
                    mainPageButton.querySelector('.like-count').textContent = data.like_count;
                }
            }
        });
};

// Edit comment
window.editComment = function (commentId) {
    const contentDiv = document.getElementById('comment-content-' + commentId);
    const editForm = document.getElementById('edit-form-' + commentId);

    if (contentDiv && editForm) {
        contentDiv.style.display = 'none';
        editForm.style.display = 'block';
    }
};

// Cancel edit comment
window.cancelEditComment = function (commentId) {
    const contentDiv = document.getElementById('comment-content-' + commentId);
    const editForm = document.getElementById('edit-form-' + commentId);

    if (contentDiv && editForm) {
        contentDiv.style.display = 'block';
        editForm.style.display = 'none';
    }
};

// Save edited comment
window.saveEditComment = function (commentId) {
    const textarea = document.getElementById('edit-textarea-' + commentId);
    const content = textarea.value.trim();

    if (!content) {
        alert('Nội dung không được để trống');
        return;
    }

    fetch(getAjaxPath(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=edit_comment&comment_id=' + commentId + '&content=' + encodeURIComponent(content),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Update content display
                const contentDiv = document.getElementById('comment-content-' + commentId);
                if (contentDiv) {
                    contentDiv.innerHTML = content.replace(/\n/g, '<br>');
                }
                cancelEditComment(commentId);
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra');
        });
};

// Delete comment
window.deleteComment = function (commentId) {
    if (!confirm('Bạn có chắc muốn xóa bình luận này?')) {
        return;
    }

    fetch(getAjaxPath(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_comment&comment_id=' + commentId,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Remove comment from DOM
                const commentElement = document.getElementById('comment-' + commentId);
                if (commentElement) {
                    commentElement.style.transition = 'opacity 0.3s';
                    commentElement.style.opacity = '0';
                    setTimeout(() => {
                        commentElement.remove();
                    }, 300);
                }
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra');
        });
};

// Modal functions
function openPostModal(postId, focusComments = false, scrollPosition = null) {
    currentPostId = postId;
    scrollToComments = focusComments;
    const modal = document.getElementById('postModal');
    const modalContent = document.getElementById('modalContent');

    modal.style.display = 'block';
    modalContent.innerHTML = '<div class="loading-spinner">⏳ Đang tải...</div>';
    document.body.style.overflow = 'hidden';

    // Load post content via AJAX
    fetch(getAjaxPath() + '?action=get_post_detail&post_id=' + postId)
        .then((response) => response.text())
        .then((html) => {
            modalContent.innerHTML = html;
            console.log('Modal content loaded, HTML length:', html.length);

            // Force re-init mention autocomplete after modal content loaded
            setTimeout(() => {
                console.log('Attempting to re-init mention autocomplete...');
                const mainTextarea = document.getElementById('main-comment-textarea');
                console.log('Main textarea found?', mainTextarea);
                if (mainTextarea && typeof initMentionAutocomplete === 'function') {
                    console.log('Re-initializing mention autocomplete for main textarea');
                    initMentionAutocomplete(mainTextarea);
                }
            }, 200);

            // Restore scroll position if provided
            if (scrollPosition !== null) {
                setTimeout(() => {
                    const modalBody = document.querySelector('.modal-body');
                    if (modalBody) {
                        modalBody.scrollTop = scrollPosition;
                    }
                }, 100);
            } else if (scrollToComments) {
                setTimeout(() => {
                    const commentsSection = document.getElementById('comments-section');
                    if (commentsSection) {
                        commentsSection.scrollIntoView({
                            behavior: 'smooth',
                        });
                    }
                }, 300);
            }
        })
        .catch((error) => {
            modalContent.innerHTML =
                '<div style="text-align: center; padding: 2rem; color: #ff7675;"><h3>❌ Lỗi khi tải bài viết</h3><p>' + error + '</p></div>';
        });
}

function closePostModal() {
    const modal = document.getElementById('postModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    currentPostId = null;
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById('postModal');
    if (event.target === modal) {
        closePostModal();
    }
};

// Close modal with ESC key
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closePostModal();
    }
});

// Menu dropdown functions
function toggleMenu(postId) {
    const menu = document.getElementById('menu-' + postId);
    // Close all other menus
    document.querySelectorAll('.dropdown-menu').forEach((m) => {
        if (m.id !== 'menu-' + postId) m.classList.remove('show');
    });
    menu.classList.toggle('show');
}

// Close menus when clicking outside
document.addEventListener('click', function (event) {
    if (!event.target.closest('.post-menu')) {
        document.querySelectorAll('.dropdown-menu').forEach((m) => m.classList.remove('show'));
    }
});

function editPost(postId) {
    window.location.href = 'pages/post.php?id=' + postId;
}

function setPrivacy(postId, privacy) {
    const privacyText = privacy === 'public' ? 'Công khai' : 'Riêng tư';
    if (confirm('Đặt quyền riêng tư thành ' + privacyText + '?')) {
        fetch(getAjaxPath(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=change_privacy&post_id=' + postId + '&privacy=' + privacy,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert('Đã đặt quyền riêng tư thành ' + privacyText + '!');
                    location.reload();
                }
            });
    }
}

function hidePost(postId) {
    if (confirm('Bạn có muốn ẩn bài viết này khỏi trang chủ?')) {
        const postCard = document.querySelector(`[onclick*="openPostModal(${postId})"]`).closest('.post-card');
        postCard.style.display = 'none';
        localStorage.setItem('hidden_post_' + postId, 'true');
        alert('Bài viết đã được ẩn');
    }
}

function reportPost(postId) {
    const reason = prompt('Lý do báo cáo:');
    if (reason) {
        fetch(getAjaxPath(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=report_post&post_id=' + postId + '&reason=' + encodeURIComponent(reason),
        })
            .then((response) => response.json())
            .then((data) => {
                alert(data.message);
            });
    }
}

// Poll functions - Vote poll inline from homepage
window.votePollInline = function (optionId, postId) {
    fetch('includes/ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=vote_poll&option_id=' + optionId,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Reload page to show updated results
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi vote');
        });
};

// Submit poll vote with AJAX (no page reload)
window.submitPollVote = function (event, pollId, postId) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const selectedOptions = formData.getAll('poll_options[]');

    if (selectedOptions.length === 0) {
        alert('Vui lòng chọn ít nhất một lựa chọn');
        return false;
    }

    // Submit each selected option
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang gửi...';

    // Submit votes for all selected options
    Promise.all(
        selectedOptions.map((optionId) =>
            fetch(getAjaxPath(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=vote_poll&option_id=' + optionId,
            }).then((res) => res.json()),
        ),
    )
        .then((results) => {
            // Check if any vote succeeded
            const anySuccess = results.some((r) => r.success);
            if (anySuccess) {
                // Fetch updated poll results
                return fetch(getAjaxPath() + '?action=get_poll_results&poll_id=' + pollId);
            } else {
                throw new Error(results[0].message || 'Có lỗi xảy ra');
            }
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Update UI with new results
                updatePollResults(postId, data.poll);
            } else {
                alert(data.message || 'Có lỗi xảy ra');
                submitBtn.disabled = false;
                submitBtn.textContent = '✓ Gửi câu trả lời';
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert(error.message || 'Có lỗi xảy ra khi vote');
            submitBtn.disabled = false;
            submitBtn.textContent = '✓ Gửi câu trả lời';
        });

    return false;
};

// Update poll results without page reload
window.updatePollResults = function (postId, pollData) {
    const container = document.getElementById('poll-container-' + postId);
    if (!container) return;

    const totalVotes = pollData.options.reduce((sum, opt) => sum + opt.vote_count, 0);

    let resultsHTML = '<div id="poll-results-' + postId + '">';
    pollData.options.forEach((option) => {
        const percentage = totalVotes > 0 ? Math.round((option.vote_count / totalVotes) * 1000) / 10 : 0;
        resultsHTML += `
            <div style="margin: 0.5rem 0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; font-size: 0.85rem;">
                    <span style="font-weight: 600;">${escapeHtml(option.option_text)}</span>
                    <span style="color: #636e72;">${option.vote_count} (${percentage}%)</span>
                </div>
                <div style="background: white; height: 6px; border-radius: 3px; overflow: hidden;">
                    <div style="background: var(--primary-mint); height: 100%; width: ${percentage}%; transition: width 0.3s;"></div>
                </div>
            </div>
        `;
    });
    resultsHTML += `
        <p style="text-align: right; color: #636e72; font-size: 0.8rem; margin-top: 0.5rem;">
            Tổng: ${totalVotes} phiếu
        </p>
    </div>`;

    // Replace form with results
    const form = document.getElementById('poll-form-' + postId);
    if (form) {
        form.outerHTML = resultsHTML;
    }
};

// ==================== NOTIFICATION SYSTEM ====================

// Toggle notification popup
function toggleNotifications() {
    const popup = document.getElementById('notificationPopup');
    if (popup.style.display === 'none' || popup.style.display === '') {
        popup.style.display = 'block';
        loadNotifications();
    } else {
        popup.style.display = 'none';
    }
}

// Attach event listener to notification button
const notificationBtn = document.getElementById('notificationBtn');
if (notificationBtn) {
    notificationBtn.addEventListener('click', toggleNotifications);
}

// Close notification popup when clicking outside
document.addEventListener('click', function (event) {
    const popup = document.getElementById('notificationPopup');
    const btn = document.querySelector('.btn-notification');

    if (popup && btn && !popup.contains(event.target) && !btn.contains(event.target)) {
        popup.style.display = 'none';
    }
});

// Load notifications via AJAX
function loadNotifications() {
    const listContainer = document.getElementById('notificationList');
    listContainer.innerHTML = '<div class="loading-spinner">⏳ Đang tải thông báo...</div>';

    fetch('includes/notifications_ajax.php?action=fetch')
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                displayNotifications(data.notifications);
                updateNotificationBadge(data.unread_count);
            } else {
                listContainer.innerHTML = '<div class="notification-empty"><div class="emoji">❌</div>Lỗi tải thông báo</div>';
            }
        })
        .catch((error) => {
            console.error('Error loading notifications:', error);
            listContainer.innerHTML = '<div class="notification-empty"><div class="emoji">❌</div>Không thể tải thông báo</div>';
        });
}

// Display notifications in popup
function displayNotifications(notifications) {
    const listContainer = document.getElementById('notificationList');

    if (notifications.length === 0) {
        listContainer.innerHTML = `
            <div class="notification-empty">
                <div class="emoji">🔕</div>
                <p>Bạn chưa có thông báo nào</p>
            </div>
        `;
        return;
    }

    let html = '';
    notifications.forEach((notif) => {
        const isUnread = notif.is_read == 0;
        const unreadClass = isUnread ? 'unread' : '';

        let icon = '🔔';
        let content = '';
        let groupedClass = '';

        if (notif.type === 'like') {
            icon = '❤️';
            groupedClass = 'notification-grouped';

            if (notif.like_count > 1 && notif.likers) {
                const names = notif.likers
                    .slice(0, 3)
                    .map((l) => l.ho_ten)
                    .join(', ');
                const remaining = notif.like_count - 3;
                content = `<strong>${names}</strong>${remaining > 0 ? ` và ${remaining} người khác` : ''} đã thích bài viết của bạn: "<em>${
                    notif.post_title || 'Bài viết'
                }</em>"`;
            } else {
                content = `<strong>${notif.actor_name}</strong> đã thích bài viết của bạn: "<em>${notif.post_title || 'Bài viết'}</em>"`;
            }
        } else if (notif.type === 'comment') {
            icon = '💬';
            content = `<strong>${notif.actor_name}</strong> đã bình luận về bài viết của bạn: "<em>${notif.post_title || 'Bài viết'}</em>"`;
        } else if (notif.type === 'mention') {
            icon = '📢';
            content = `<strong>${notif.actor_name}</strong> đã nhắc đến bạn trong ${notif.target_type === 'post' ? 'bài viết' : 'bình luận'}: "<em>${
                notif.content || notif.post_title || 'Xem chi tiết'
            }</em>"`;
        } else {
            content = notif.content || 'Thông báo hệ thống';
        }

        const timeAgo = formatTimeAgo(notif.created_at);
        const postLink = notif.post_id ? `pages/post.php?id=${notif.post_id}` : '#';

        html += `
            <div class="notification-item ${unreadClass} ${groupedClass}" data-id="${notif.id}" onclick="handleNotificationClick(${notif.id}, '${postLink}')">
                <div class="notification-icon-type">${icon}</div>
                <div class="notification-content">
                    <div>${content}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        `;
    });

    listContainer.innerHTML = html;
}

// Handle notification click
function handleNotificationClick(notifId, postLink) {
    // Immediately update UI to show read state
    const item = document.querySelector(`.notification-item[data-id="${notifId}"]`);
    if (item) {
        item.classList.remove('unread');
        item.classList.add('read');
    }

    // Mark as read on server
    fetch('includes/notifications_ajax.php?action=mark_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notifId}`,
    }).then(() => {
        updateNotificationCount();
    });

    // Navigate to post
    if (postLink && postLink !== '#') {
        window.location.href = postLink;
    }
}

// Mark all notifications as read
function markAllAsRead() {
    // Immediately fade all unread notifications in UI
    document.querySelectorAll('.notification-item.unread').forEach((el) => {
        el.classList.remove('unread');
        el.classList.add('read');
    });

    const btn = document.querySelector('.mark-all-read');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';
    }

    fetch('includes/notifications_ajax.php?action=mark_all_read', {
        method: 'POST',
    })
        .then((response) => response.json())
        .then((data) => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Đánh dấu tất cả đã đọc';
            }
            if (data.success) {
                loadNotifications();
                updateNotificationBadge(0);
            }
        })
        .catch(() => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Đánh dấu tất cả đã đọc';
            }
        });
}

// Update notification badge count
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Update notification count (fetch from server)
function updateNotificationCount() {
    fetch('includes/notifications_ajax.php?action=count')
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
            }
        });
}

// Format time ago
function formatTimeAgo(timestamp) {
    const now = new Date();
    const date = new Date(timestamp);
    const diff = Math.floor((now - date) / 1000); // seconds

    if (diff < 60) return 'Vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
    if (diff < 604800) return Math.floor(diff / 86400) + ' ngày trước';

    return date.toLocaleDateString('vi-VN');
}

// Initialize notifications on page load
if (document.querySelector('.btn-notification')) {
    // Update notification count every 30 seconds
    updateNotificationCount();
    setInterval(updateNotificationCount, 30000);
}
