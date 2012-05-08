package de.weltraumschaf.ebnf;

import de.weltraumschaf.ebnf.ast.nodes.Syntax;
import de.weltraumschaf.ebnf.util.ReaderHelper;
import de.weltraumschaf.ebnf.visitor.TextSyntaxTree;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import org.apache.commons.cli.HelpFormatter;
import org.apache.commons.cli.ParseException;

/**
 * Main application class.
 */
public class App {

    public enum ExitCode {
        OK(0),
        READ_ERROR(1),
        NO_SYNTAX(2),
        SYNTAX_ERROR(3),
        FATAL_ERROR(-1);

        private final int code;

        private ExitCode(int code) {
            this.code    = code;
        }

        public int getCode() {
            return code;
        }

    }

    private final String[] args;

    public App(String[] args) {
        this.args = args;
    }

    public static void main(String[] args) {
        try {
            App app = new App(args);
            exit(app.run());
        } catch (Error e) {
            System.out.println(e.getMessage());
            exit(e.getCode());
        } catch (Throwable t) {
            System.out.println("Fatal error!");
            t.printStackTrace(System.out);
            exit(ExitCode.FATAL_ERROR);
        }
    }

    private static void exit(ExitCode code) {
        exit(code.getCode());
    }

    private static void exit(int code) {
        System.exit(code);
    }

    private ExitCode run()  throws Error {
        CliOptions options = parseOptions();

        if (options.isHelp()) {
            options.format(new HelpFormatter());
            return ExitCode.OK;
        }

        if (!options.hasSyntaxFile()) {
            System.out.println("No syntax file given!");
            return ExitCode.NO_SYNTAX;
        }

        Scanner scanner;
        Parser parser;
        Syntax ast;

        try {
            scanner = new Scanner(ReaderHelper.createFrom(new File(options.getSyntaxFile())));
            parser  = new Parser(scanner);
            ast     = parser.parse();
        } catch (FileNotFoundException ex) {
            System.out.println(String.format("Can not read syntax file '%s'!", options.getSyntaxFile()));

            if (options.isDebug()) {
                ex.printStackTrace(System.out);
            }

            return ExitCode.READ_ERROR;
        } catch (IOException ex) {
            System.out.println(String.format("Can not read syntax file '%s'!", options.getSyntaxFile()));

            if (options.isDebug()) {
                ex.printStackTrace(System.out);
            }

            return ExitCode.READ_ERROR;
        }

        if (options.isTextTree()) {
            TextSyntaxTree visitor = new TextSyntaxTree();
            ast.accept(visitor);
            System.out.println(visitor.getText());
        }

        return ExitCode.OK;
    }

    private CliOptions parseOptions() throws Error {
        CliOptions options = new CliOptions();

        try {
            options.parse(args);
        } catch (ParseException ex) {
            throw new Error(ex.getMessage(), 1, ex);
        }

        return options;
    }
}