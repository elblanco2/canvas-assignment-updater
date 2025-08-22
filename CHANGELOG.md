# Changelog

All notable changes to the Canvas Assignment Due Date Updater will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-08-21

### 🚀 Added
- **Canvas Assignment Fetching**: Fetch assignments directly from Canvas for manual matching
- **Manual Assignment Matching**: Interface to manually match assignments that AI missed
- **Session Management**: Logout and reset functionality to clear API keys and data
- **Version Control**: GitHub integration with version display and repository links
- **Enhanced UI**: Cleaner interface with session controls and version information
- **Better Error Handling**: More detailed error messages and debugging information

### 🔧 Fixed
- **Step Progression Bug**: Fixed nested HTML forms causing users to jump back to step 1
- **Form Validation**: Improved form handling and POST data processing
- **Assignment Matching**: More flexible Canvas assignment name matching
- **Session Persistence**: Better session state management across requests

### 🎨 Enhanced
- **AI Chatbot**: More contextual responses and Fall 2025 specific guidance
- **User Experience**: Streamlined workflow with better instructions
- **Documentation**: Comprehensive README and changelog
- **Code Organization**: Better structured code with proper version constants

### 🛡️ Security
- **Session Security**: Proper session destruction on logout
- **API Key Management**: Secure handling of temporary API key storage

## [1.0.0] - 2025-08-20

### 🚀 Initial Release
- **Multi-step Wizard**: Three-step process (API Setup → Assignment Matching → Canvas Update)
- **AI Integration**: Google Gemini (free) and Claude API support
- **Fall 2025 Schedule**: Automatic generation of realistic academic schedules
- **Canvas API**: Direct integration with Canvas LMS for assignment updates
- **Intelligent Matching**: AI-powered semantic assignment name matching
- **Real-time Chat**: AI assistant for user guidance and support
- **Editable Dates**: Manual adjustment of matched due dates
- **Responsive Design**: Mobile-friendly interface

### 🎯 Core Features
- Assignment list parsing and matching
- Due date generation with academic calendar awareness
- Canvas API authentication and course management
- Error handling and user feedback
- Session-based workflow management

---

## 🔮 Planned Features (Future Versions)

### Version 2.1.0 (Planned)
- [ ] **Bulk Course Processing**: Handle multiple courses at once
- [ ] **Schedule Templates**: Save and reuse custom schedule templates
- [ ] **Assignment Categories**: Support for weighted grade categories
- [ ] **Time Zone Support**: Automatic time zone detection and conversion

### Version 2.2.0 (Planned)
- [ ] **Calendar Integration**: Export to Google Calendar, Outlook, etc.
- [ ] **Email Notifications**: Send updates to students automatically
- [ ] **Assignment Descriptions**: Update assignment descriptions along with due dates
- [ ] **Recurring Schedules**: Support for recurring assignment patterns

### Version 3.0.0 (Planned)
- [ ] **Database Storage**: Persistent storage for course templates and history
- [ ] **User Accounts**: Multi-user support with saved preferences
- [ ] **API Rate Limiting**: Built-in rate limiting for large course updates
- [ ] **Advanced Analytics**: Usage statistics and success metrics

---

## 📊 Statistics

- **Lines of Code**: ~1,500 (v2.0.0)
- **Supported AI Providers**: 2 (Gemini, Claude)
- **Supported LMS**: 1 (Canvas)
- **Test Coverage**: Manual testing (automated tests planned for v2.1.0)

## 🤝 Contributors

- **Claude AI Assistant** - Primary development and architecture
- **Luca** - Testing, requirements, and user feedback

---

*This changelog is maintained manually and updated with each release.*