const contactForm = document.getElementById("contactForm");
const contactResult = document.getElementById("contactResult");

function createQuoteContactMessage(form) {
  const labels = {
    request_type: "Request type",
    name: "Name",
    email: "Email",
    phone: "Phone / WhatsApp",
    type: "Shipment type",
    origin: "Origin",
    destination: "Destination",
    mode: "Freight mode",
    weight: "Weight (kg)",
    product_category: "Product category",
    quantity: "Quantity",
    market: "Preferred sourcing market",
    budget: "Budget range",
    notes: "Cargo notes",
    product_details: "Product details"
  };
  const lines = ["Bani Global Logistics quote request"];
  new FormData(form).forEach(function (value, key) {
    const text = String(value).trim();
    if (key !== "request_type" && text) {
      lines.push((labels[key] || key) + ": " + text);
    }
  });
  return lines.join("\n");
}

function appendQuoteContactActions(result, message, emailAccepted, requestSaved) {
  const note = document.createElement("p");
  note.textContent = requestSaved
    ? (emailAccepted
      ? "Operations has been notified by email. To send a WhatsApp copy too, open WhatsApp below and tap Send."
      : "We couldn't confirm the email notification. Your request may be saved; use WhatsApp or email below to contact operations.")
    : "The website couldn't confirm your request. You can still send it directly to operations using either option below.";
  result.appendChild(note);

  const actions = document.createElement("div");
  actions.className = "hero-actions quote-followup-actions";

  const whatsappLink = document.createElement("a");
  whatsappLink.className = "button primary";
  whatsappLink.href = "https://wa.me/254782013236?text=" + encodeURIComponent(message);
  whatsappLink.target = "_blank";
  whatsappLink.rel = "noopener noreferrer";
  whatsappLink.textContent = "Send WhatsApp copy";

  const emailLink = document.createElement("a");
  emailLink.className = "button secondary";
  emailLink.href = "mailto:operations@banilogistics.co.ke?subject="
    + encodeURIComponent("Website quote request")
    + "&body=" + encodeURIComponent(message);
  emailLink.textContent = "Email operations";

  actions.appendChild(whatsappLink);
  actions.appendChild(emailLink);
  result.appendChild(actions);
}

async function submitQuoteRequest(formId, resultId) {
  const form = document.getElementById(formId);
  const result = document.getElementById(resultId);

  if (!form || !result) {
    return;
  }

  form.addEventListener("submit", async function (event) {
    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    const originalLabel = button ? button.textContent : "";
    const contactMessage = createQuoteContactMessage(form);
    result.className = "result-box show";
    result.textContent = "Sending your request…";

    if (button) {
      button.disabled = true;
      button.textContent = "Sending…";
    }

    try {
      const response = await fetch(form.action || "quote-request.php", {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json" }
      });
      const payload = await response.json();

      if (!response.ok || !payload.success) {
        throw new Error(payload.message || "We couldn’t submit your request. Please try again.");
      }

      result.className = "result-box show result-success";
      result.textContent = payload.message || "Your quote request has been received.";
      appendQuoteContactActions(
        result,
        contactMessage,
        payload.email_notification_accepted === true,
        true
      );
      form.reset();
      result.focus();
    } catch (error) {
      result.className = "result-box show result-error";
      result.textContent = error.message || "We couldn’t send your request. Please try again or contact our team directly.";
      result.focus();
    } finally {
      if (button) {
        button.disabled = false;
        button.textContent = originalLabel;
      }
    }
  });
}

submitQuoteRequest("shippingQuoteForm", "shippingQuoteResult");
submitQuoteRequest("sourcingQuoteForm", "sourcingQuoteResult");

function baniChatbotReply(message) {
  const text = (message || "").toLowerCase();

  if (text.includes("invoice") && (text.includes("pay") || text.includes("payment"))) {
    return "Open your invoice link in the client portal to see the payment instructions. If you pay outside the platform, submit the payment reference in the invoice page so accounts can confirm it.";
  }

  if (text.includes("track") || text.includes("shipment")) {
    return "Use the tracking page or your client dashboard to view shipment progress. If a shipment has been created for your account, it will appear in your portal with its next milestone.";
  }

  if (text.includes("quote") || text.includes("pricing") || text.includes("rate")) {
    return "Quotes are reviewed manually while our rate card is being prepared. Send a shipping or sourcing request through the quote form, and our operations team will follow up by email or WhatsApp.";
  }

  if (text.includes("sourcing") || text.includes("source")) {
    return "For sourcing requests, share the product name, quantity, preferred market, budget, and any links or specifications you already have. That helps the team respond faster.";
  }

  if (text.includes("country") || text.includes("usa") || text.includes("australia") || text.includes("hong kong") || text.includes("china") || text.includes("germany")) {
    return "Bani can support shipping and sourcing across major global markets including Australia, the USA, Hong Kong, China, Germany, the UAE, the UK, and other supplier regions depending on the cargo and route.";
  }

  if (text.includes("contact") || text.includes("support") || text.includes("speak to someone")) {
    return "You can reach operations at operations@banilogistics.co.ke or WhatsApp +254 782 013 236. For invoice questions, contact accounts@banilogistics.co.ke.";
  }

  if (/^(hi|hello|hey|good morning|good afternoon)[!. ]*$/.test(text.trim())) {
    return "Hello! I can help with general tracking, invoice payment steps, shipping coverage, sourcing requests, and quote requests.";
  }

  return "I can help with tracking shipments, invoice payment guidance, quote requests, sourcing requirements, and contact details. Try asking about tracking, payment, or quotes.";
}

function initializeBaniChatbot() {
  if (document.body.dataset.baniChatbotReady === "1") {
    return;
  }

  document.body.dataset.baniChatbotReady = "1";

  const toggle = document.createElement("button");
  toggle.type = "button";
  toggle.className = "bani-chatbot-toggle";
  toggle.textContent = "Hey Bani";

  const panel = document.createElement("section");
  panel.className = "bani-chatbot";
  panel.innerHTML =
    '<div class="bani-chatbot-header">' +
      '<div><strong>Bani</strong><span>Your logistics assistant</span></div>' +
      '<button type="button" class="bani-chatbot-chip" data-close-chatbot>Close</button>' +
    "</div>" +
    '<div class="bani-chatbot-body">' +
      '<div class="bani-chatbot-messages" id="baniChatbotMessages">' +
        '<div class="bani-chatbot-message bot">Hello, I am Bani. Ask me about tracking, invoices, payment details, sourcing requests, or shipping coverage.</div>' +
      "</div>" +
      '<div class="bani-chatbot-actions">' +
        '<button type="button" class="bani-chatbot-chip" data-chatbot-prompt="How do I pay an invoice?">Invoice payment</button>' +
        '<button type="button" class="bani-chatbot-chip" data-chatbot-prompt="How do I track my shipment?">Track shipment</button>' +
        '<button type="button" class="bani-chatbot-chip" data-chatbot-prompt="What details do you need for sourcing?">Sourcing help</button>' +
      "</div>" +
      '<form class="bani-chatbot-form" id="baniChatbotForm">' +
        '<input type="text" id="baniChatbotInput" placeholder="Ask Bani a question">' +
        '<button type="submit">Send</button>' +
      "</form>" +
    "</div>";

  document.body.appendChild(toggle);
  document.body.appendChild(panel);

  const messages = document.getElementById("baniChatbotMessages");
  const form = document.getElementById("baniChatbotForm");
  const input = document.getElementById("baniChatbotInput");
  const closeButton = panel.querySelector("[data-close-chatbot]");
  const promptButtons = panel.querySelectorAll("[data-chatbot-prompt]");

  function appendMessage(kind, text) {
    if (!messages) {
      return;
    }

    const bubble = document.createElement("div");
    bubble.className = "bani-chatbot-message " + kind;
    bubble.textContent = text;
    messages.appendChild(bubble);
    messages.scrollTop = messages.scrollHeight;
  }

  function submitPrompt(prompt) {
    if (!prompt) {
      return;
    }

    appendMessage("user", prompt);
    appendMessage("bot", baniChatbotReply(prompt));
  }

  toggle.addEventListener("click", function () {
    panel.classList.toggle("show");
  });

  if (closeButton) {
    closeButton.addEventListener("click", function () {
      panel.classList.remove("show");
    });
  }

  promptButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      submitPrompt(button.getAttribute("data-chatbot-prompt") || "");
    });
  });

  if (form && input) {
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      const prompt = input.value.trim();
      if (!prompt) {
        return;
      }
      submitPrompt(prompt);
      input.value = "";
    });
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeBaniChatbot);
} else {
  initializeBaniChatbot();
}



const trackingInput = document.getElementById("trackingInput");
const trackButton = document.getElementById("trackButton");
const trackingStatus = document.getElementById("trackingStatus");
const statusBadge = document.getElementById("statusBadge");
const statusTitle = document.getElementById("statusTitle");
const statusText = document.getElementById("statusText");
const statusSource = document.getElementById("statusSource");
const statusDetails = document.getElementById("statusDetails");

const trackingData = {
  BG000001: {
    badge: "In Transit",
    title: "Shipment is moving to the destination hub.",
    text: "Cargo has departed the origin station and is now in line-haul transit.",
    details: [
      "Reference: BG000001",
      "Last checkpoint: Dubai transit hub",
      "Next step: Arrival scan at destination"
    ]
  },
  BG000014: {
    badge: "Customs",
    title: "Shipment is currently under customs handling.",
    text: "Documentation review and clearance processing are in progress.",
    details: [
      "Reference: BG000014",
      "Last checkpoint: Port customs office",
      "Next step: Release for dispatch"
    ]
  },
  BG000027: {
    badge: "Out for Delivery",
    title: "Shipment is on the final delivery route.",
    text: "Cargo has cleared operations and is assigned for last-mile handoff.",
    details: [
      "Reference: BG000027",
      "Last checkpoint: Nairobi delivery station",
      "Next step: Customer delivery confirmation"
    ]
  }
};

function renderStatusCard(entry) {
  if (!trackingStatus || !statusBadge || !statusTitle || !statusText || !statusDetails) {
    return;
  }

  statusBadge.textContent = entry.badge;
  statusTitle.textContent = entry.title;
  statusText.textContent = entry.text;

  if (statusSource) {
    statusSource.textContent = entry.source || "";
  }

  statusDetails.replaceChildren();
  entry.details.forEach(function (item) {
    const detail = document.createElement("li");
    detail.textContent = item;
    statusDetails.appendChild(detail);
  });
}

function fallbackTrackingResult(key) {
  return {
    badge: "Not Found",
    title: "No shipment was found for that reference.",
    text: "Check the reference and try again, or contact operations for help.",
    source: "General public tracking",
    details: ["Detailed shipment updates are available after signing in to the client portal."]
  };
}

function mapApiTrackingResult(payload, key) {
  if (!payload || payload.success !== true || !payload.public_status) {
    return fallbackTrackingResult(key);
  }

  const rawStatus = String(payload.public_status).toLowerCase();
  let publicStatus = "Processing";
  if (rawStatus.includes("deliver")) {
    publicStatus = "Delivered";
  } else if (rawStatus.includes("customs") || rawStatus.includes("clearance")) {
    publicStatus = "At customs";
  } else if (rawStatus.includes("port") || rawStatus.includes("terminal")) {
    publicStatus = "At port";
  } else if (rawStatus.includes("transit") || rawStatus.includes("depart")) {
    publicStatus = "In transit";
  }

  return {
    badge: publicStatus,
    title: "Shipment status: " + publicStatus + ".",
    text: "This is a general public update. Sign in to the client portal for detailed progress and invoice information.",
    source: "General public tracking",
    details: ["Reference: " + key, "Sign in to the client portal for detailed updates."]
  };
}

async function fetchTrackingResult(key) {
  try {
    const response = await fetch("/public-tracking.php?reference=" + encodeURIComponent(key), {
      headers: { Accept: "application/json" }
    });
    const payload = await response.json();
    return mapApiTrackingResult(payload, key);
  } catch (error) {
    return {
      badge: "Unavailable",
      title: "Tracking is temporarily unavailable.",
      text: "Please try again shortly or contact operations for help.",
      source: "General public tracking",
      details: ["Detailed shipment updates remain available in the client portal."]
    };
  }
}

async function renderTrackingResult() {
  if (!trackingInput || !trackingStatus || !statusBadge || !statusTitle || !statusText || !statusDetails) {
    return;
  }

  const key = trackingInput.value.trim().toUpperCase();

  if (!key) {
    renderStatusCard({
      badge: "Tracking Status",
      title: "Enter a tracking number.",
      text: "Enter the shipment reference for a general status update.",
      source: "General public tracking",
      details: ["Sign in to the client portal for detailed progress and invoice information."]
    });
    return;
  }

  renderStatusCard({
    badge: "Searching",
    title: "Looking up shipment details.",
    text: "Please wait while we check the live tracking system.",
    source: "Tracking source: checking API",
    details: ["Reference: " + key]
  });

  const result = await fetchTrackingResult(key);
  renderStatusCard(result);
}

if (trackButton) {
  trackButton.addEventListener("click", renderTrackingResult);
}

if (trackingInput) {
  trackingInput.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      renderTrackingResult();
    }
  });
}


// Accessible collapsible navigation for smaller screens.
document.querySelectorAll(".nav-toggle").forEach(function (button) {
  const navId = button.getAttribute("aria-controls");
  const nav = navId ? document.getElementById(navId) : null;

  if (!nav) {
    return;
  }

  function setExpanded(expanded) {
    button.setAttribute("aria-expanded", String(expanded));
    nav.classList.toggle("is-open", expanded);
  }

  button.addEventListener("click", function () {
    setExpanded(button.getAttribute("aria-expanded") !== "true");
  });

  nav.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", function () {
      setExpanded(false);
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && button.getAttribute("aria-expanded") === "true") {
      setExpanded(false);
      button.focus();
    }
  });
});
