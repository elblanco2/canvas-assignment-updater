# Canvas Assignment Due Date Updater

An AI-powered web application for updating Canvas assignment due dates using intelligent matching and Fall 2025 schedule generation.

## 🚀 Features

- **Multi-step Wizard Interface**: Clean, user-friendly workflow
- **AI-Powered Matching**: Uses Google Gemini (free) or Claude API for intelligent assignment matching
- **Canvas API Integration**: Direct integration with Canvas LMS
- **Manual Assignment Matching**: Fetch and manually match assignments that AI missed
- **Session Management**: Secure logout and data clearing
- **Fall 2025 Schedule Generation**: Automatically generates realistic academic schedules
- **Real-time Chat Assistant**: AI chatbot for help and guidance
- **Editable Due Dates**: Manual adjustment of matched dates before updating

## 📋 Requirements

- PHP 7.4+
- cURL extension enabled
- Canvas API access token
- Google Gemini API key (free) or Claude API key (paid)

## 🛠️ Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/elblanco2/canvas-assignment-updater.git
   cd canvas-assignment-updater
   ```

2. **Upload to web server:**
   - Upload `canvas_wizard_v2.php` to your web server
   - Ensure PHP and cURL are enabled

3. **Get API Keys:**
   - **Gemini (Free)**: https://makersuite.google.com/app/apikey
   - **Claude (Paid)**: https://console.anthropic.com/

4. **Get Canvas API Token:**
   - Go to Canvas → Account → Settings
   - Scroll to "Approved Integrations"
   - Click "+ New Access Token"
   - Copy the generated token

## 🎯 Usage

### Step 1: API Setup
1. Open the web application
2. Enter your Google Gemini or Claude API key
3. Select preferred AI provider
4. Click "Continue to Assignment Matching"

### Step 2: Match Assignments
1. Copy your Canvas assignment list:
   - Go to Canvas → Assignments
   - Select all assignment names (Ctrl+A)
   - Copy them (Ctrl+C)
2. Paste the list in the text area
3. Click "Generate Fall 2025 Schedule"
4. Review AI matching results
5. Use manual matching for any missed assignments
6. Click "Proceed to Canvas Update"

### Step 3: Canvas Update
1. Enter Canvas API credentials:
   - Canvas API URL (e.g., `https://your-school.instructure.com/api/v1`)
   - API Token
   - Course ID
2. Review the update preview
3. Edit any dates if needed
4. Click "Update Canvas Assignments"

## 🤖 AI Assistant

The built-in AI chatbot can help you with:
- Generating Fall 2025 schedules
- Understanding assignment matching
- Canvas API guidance
- Date recommendations
- Troubleshooting

Example questions:
- "Generate a typical Fall 2025 schedule for my course"
- "Are these dates good for Fall 2025?"
- "Where do I find my Canvas API token?"

## 📁 File Structure

```
canvas-assignment-updater/
├── canvas_wizard_v2.php    # Main application (Version 2.0)
├── canvas_wizard.php       # Legacy version (1.0)
├── README.md               # This file
├── CHANGELOG.md            # Version history
├── LICENSE                 # MIT License
└── .gitignore             # Git ignore file
```

## 🔄 Version History

### Version 2.0.0 (Latest)
- ✅ Added Canvas assignment fetching for manual matching
- ✅ Added session logout and reset functionality  
- ✅ Improved error handling and user feedback
- ✅ Enhanced UI with version control and GitHub integration
- ✅ Fixed nested form bugs causing step progression issues

### Version 1.0.0
- ✅ Basic AI-powered assignment matching
- ✅ Fall 2025 schedule generation
- ✅ Canvas API integration
- ✅ Multi-step wizard interface

## 🛡️ Security

- API keys are stored in PHP sessions (not persistent)
- Use HTTPS in production
- Logout functionality clears all session data
- Canvas API tokens are not stored permanently

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙋‍♂️ Support

- **Issues**: [GitHub Issues](https://github.com/elblanco2/canvas-assignment-updater/issues)
- **Discussions**: [GitHub Discussions](https://github.com/elblanco2/canvas-assignment-updater/discussions)
- **Email**: Contact via GitHub

## 🎓 Use Cases

Perfect for:
- Faculty updating assignment due dates for new semesters
- Course coordinators managing multiple sections
- Academic administrators handling course transfers
- Anyone needing to bulk update Canvas assignments

---

**Made with ❤️ by Claude AI Assistant**