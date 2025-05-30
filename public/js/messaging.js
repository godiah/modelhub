// // Messaging System JavaScript
// class MessageModal {
//     static isOpen = false;
//     static engagementId = null;
//     static currentUserId = null;
//     static canSendMessages = false;
//     static pollInterval = null;

//     static init() {
//         // Get current user ID from meta tag or global variable
//         const userIdMeta = document.querySelector('meta[name="user-id"]');
//         this.currentUserId = userIdMeta
//             ? Number.parseInt(userIdMeta.getAttribute("content"))
//             : null;

//         // Set up periodic polling for new messages
//         this.pollInterval = setInterval(() => {
//             if (this.isOpen && this.engagementId) {
//                 this.fetchMessages(false);
//             }
//         }, 10000); // Poll every 10 seconds

//         // Fetch unread counts when page loads
//         this.updateUnreadCounts();

//         // Set up periodic unread count updates
//         setInterval(() => {
//             this.updateUnreadCounts();
//         }, 60000); // Every minute
//     }

//     static open(engagementId) {
//         this.engagementId = engagementId;
//         this.isOpen = true;

//         const modal = document.getElementById("message-modal");
//         const overlay = document.getElementById("modal-overlay");
//         const panel = document.getElementById("modal-panel");

//         // Show modal
//         modal.classList.remove("hidden");

//         // Animate in
//         setTimeout(() => {
//             overlay.classList.remove("opacity-0");
//             overlay.classList.add("opacity-100");
//             panel.classList.remove(
//                 "opacity-0",
//                 "translate-y-4",
//                 "sm:translate-y-0",
//                 "sm:scale-95"
//             );
//             panel.classList.add("opacity-100", "translate-y-0", "sm:scale-100");
//         }, 10);

//         // Prevent body scrolling
//         document.body.style.overflow = "hidden";

//         // Reset modal state
//         this.showLoading();
//         this.clearMessages();
//         this.clearInput();

//         // Fetch messages
//         this.fetchMessages(true);
//     }

//     static close() {
//         const modal = document.getElementById("message-modal");
//         const overlay = document.getElementById("modal-overlay");
//         const panel = document.getElementById("modal-panel");

//         // Animate out
//         overlay.classList.remove("opacity-100");
//         overlay.classList.add("opacity-0");
//         panel.classList.remove("opacity-100", "translate-y-0", "sm:scale-100");
//         panel.classList.add(
//             "opacity-0",
//             "translate-y-4",
//             "sm:translate-y-0",
//             "sm:scale-95"
//         );

//         // Hide modal after animation
//         setTimeout(() => {
//             modal.classList.add("hidden");
//             this.isOpen = false;
//             this.engagementId = null;
//         }, 200);

//         // Restore body scrolling
//         document.body.style.overflow = "";
//     }

//     static showLoading() {
//         document.getElementById("loading-spinner").classList.remove("hidden");
//         document.getElementById("messages-container").classList.add("hidden");
//         document.getElementById("empty-state").classList.add("hidden");
//     }

//     static hideLoading() {
//         document.getElementById("loading-spinner").classList.add("hidden");
//     }

//     static clearMessages() {
//         document.getElementById("messages-container").innerHTML = "";
//     }

//     static clearInput() {
//         document.getElementById("message-input").value = "";
//     }

//     static async fetchMessages(scrollToBottom = false) {
//         if (!this.engagementId) return;

//         try {
//             console.log("Fetching messages for engagement:", this.engagementId); // Debug log
//             const response = await fetch(
//                 `/chat/engagements/${this.engagementId}/messages`
//             );

//             if (!response.ok) {
//                 console.error(
//                     "Response not OK:",
//                     response.status,
//                     response.statusText
//                 );
//                 this.hideLoading();
//                 return;
//             }

//             const data = await response.json();
//             console.log("Messages data:", data); // Debug log

//             this.hideLoading();

//             // Update engagement details
//             const engagement = data.engagement;
//             document.getElementById(
//                 "engagement-title"
//             ).textContent = `Engagement #${engagement.id}`;

//             // Determine other party name
//             const isClient = engagement.client.id === this.currentUserId;
//             const otherPartyName = isClient
//                 ? engagement.freelancer.name
//                 : engagement.client.name;
//             document.getElementById("other-party-name").textContent =
//                 otherPartyName;

//             // Check if user can send messages
//             this.canSendMessages = ["active", "cancelled"].includes(
//                 engagement.status
//             );
//             this.updateSendButton();

//             // Display messages
//             this.displayMessages(data.messages);

//             if (scrollToBottom) {
//                 setTimeout(() => this.scrollToBottom(), 100);
//             }

//             // Update unread counts
//             this.updateUnreadCounts();
//         } catch (error) {
//             console.error("Error fetching messages:", error);
//             this.hideLoading();
//         }
//     }

//     static displayMessages(messages) {
//         const container = document.getElementById("messages-container");
//         const emptyState = document.getElementById("empty-state");

//         if (messages.length === 0) {
//             container.classList.add("hidden");
//             emptyState.classList.remove("hidden");
//             return;
//         }

//         container.classList.remove("hidden");
//         emptyState.classList.add("hidden");

//         container.innerHTML = messages
//             .map((message) => this.createMessageHTML(message))
//             .join("");
//     }

//     static createMessageHTML(message) {
//         const isOwnMessage = message.sender_id === this.currentUserId;
//         const alignmentClass = isOwnMessage
//             ? "ml-auto bg-secondary text-white"
//             : "mr-auto bg-white border border-neutral-200";
//         const senderName = isOwnMessage ? "You" : message.sender.name;
//         const textColorClass = isOwnMessage ? "text-white" : "text-primary";
//         const timeColorClass = isOwnMessage
//             ? "text-white/80"
//             : "text-neutral-500";
//         const contentColorClass = isOwnMessage
//             ? "text-white"
//             : "text-neutral-800";

//         return `
//               <div class="max-w-[75%] rounded-lg px-4 py-2 mb-3 shadow-sm ${alignmentClass}">
//                   <div class="flex justify-between items-center mb-1">
//                       <span class="font-medium text-sm ${textColorClass}">${senderName}</span>
//                       <span class="text-xs ${timeColorClass}">${this.formatDate(
//             message.created_at
//         )}</span>
//                   </div>
//                   <p class="text-sm whitespace-pre-wrap break-words ${contentColorClass}">${this.escapeHtml(
//             message.content
//         )}</p>
//               </div>
//           `;
//     }

//     static async sendMessage(event) {
//         event.preventDefault();

//         if (!this.engagementId || !this.canSendMessages) return;

//         const input = document.getElementById("message-input");
//         const content = input.value.trim();

//         if (!content) return;

//         // Clear input immediately for better UX
//         input.value = "";

//         try {
//             console.log("Sending message:", content); // Debug log
//             const response = await fetch(
//                 `/chat/engagements/${this.engagementId}/messages`,
//                 {
//                     method: "POST",
//                     headers: {
//                         "Content-Type": "application/json",
//                         "X-CSRF-TOKEN": document
//                             .querySelector('meta[name="csrf-token"]')
//                             .getAttribute("content"),
//                     },
//                     body: JSON.stringify({ content }),
//                 }
//             );

//             if (!response.ok) {
//                 console.error(
//                     "Response not OK:",
//                     response.status,
//                     response.statusText
//                 );
//                 input.value = content; // Restore message
//                 return;
//             }

//             const data = await response.json();
//             console.log("Send message response:", data); // Debug log

//             if (data.success) {
//                 // Refresh messages to show the new one
//                 await this.fetchMessages(false);
//                 this.scrollToBottom();
//             }
//         } catch (error) {
//             console.error("Error sending message:", error);
//             // Restore the message if sending failed
//             input.value = content;
//         }
//     }

//     static handleKeyDown(event) {
//         if (event.key === "Enter" && !event.shiftKey) {
//             event.preventDefault();
//             this.sendMessage(event);
//         }
//     }

//     static updateSendButton() {
//         const button = document.getElementById("send-button");
//         const input = document.getElementById("message-input");
//         const notice = document.getElementById("messaging-disabled-notice");

//         if (this.canSendMessages) {
//             button.disabled = false;
//             input.disabled = false;
//             button.classList.remove("opacity-50", "cursor-not-allowed");
//             notice.classList.add("hidden");
//         } else {
//             button.disabled = true;
//             input.disabled = true;
//             button.classList.add("opacity-50", "cursor-not-allowed");
//             notice.classList.remove("hidden");
//         }
//     }

//     static scrollToBottom() {
//         const messageList = document.getElementById("message-list");
//         if (messageList) {
//             messageList.scrollTop = messageList.scrollHeight;
//         }
//     }

//     static async updateUnreadCounts() {
//         try {
//             console.log("Fetching unread counts..."); // Debug log
//             const response = await fetch("/chat/messages/unread-count");

//             if (!response.ok) {
//                 console.error(
//                     "Response not OK:",
//                     response.status,
//                     response.statusText
//                 );
//                 return;
//             }

//             const data = await response.json();
//             console.log("Unread counts data:", data); // Debug log

//             // Update unread badges for all message buttons
//             document
//                 .querySelectorAll("[data-engagement-id]")
//                 .forEach((button) => {
//                     const engagementId =
//                         button.getAttribute("data-engagement-id");
//                     const badge = button.querySelector(".unread-badge");
//                     const count = data.unread_counts[engagementId] || 0;

//                     if (count > 0) {
//                         badge.textContent = count;
//                         badge.classList.remove("hidden");
//                     } else {
//                         badge.classList.add("hidden");
//                     }
//                 });
//         } catch (error) {
//             console.error("Error fetching unread counts:", error);
//         }
//     }

//     static formatDate(dateString) {
//         const date = new Date(dateString);
//         const now = new Date();
//         const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

//         if (diffDays === 0) {
//             // Today, show time only
//             return date.toLocaleTimeString([], {
//                 hour: "2-digit",
//                 minute: "2-digit",
//             });
//         } else if (diffDays === 1) {
//             // Yesterday
//             return (
//                 "Yesterday " +
//                 date.toLocaleTimeString([], {
//                     hour: "2-digit",
//                     minute: "2-digit",
//                 })
//             );
//         } else if (diffDays < 7) {
//             // Within a week
//             const days = [
//                 "Sunday",
//                 "Monday",
//                 "Tuesday",
//                 "Wednesday",
//                 "Thursday",
//                 "Friday",
//                 "Saturday",
//             ];
//             return (
//                 days[date.getDay()] +
//                 " " +
//                 date.toLocaleTimeString([], {
//                     hour: "2-digit",
//                     minute: "2-digit",
//                 })
//             );
//         } else {
//             // Older than a week
//             return (
//                 date.toLocaleDateString() +
//                 " " +
//                 date.toLocaleTimeString([], {
//                     hour: "2-digit",
//                     minute: "2-digit",
//                 })
//             );
//         }
//     }

//     static escapeHtml(text) {
//         const div = document.createElement("div");
//         div.textContent = text;
//         return div.innerHTML;
//     }
// }

// // Initialize when DOM is loaded
// document.addEventListener("DOMContentLoaded", () => {
//     MessageModal.init();
// });

// // Handle escape key to close modal
// document.addEventListener("keydown", (event) => {
//     if (event.key === "Escape" && MessageModal.isOpen) {
//         MessageModal.close();
//     }
// });
